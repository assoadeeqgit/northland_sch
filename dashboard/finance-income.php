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

// Page title and user info
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Statement - Northland Schools</title>
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
            <h1 class="text-2xl font-bold text-gray-900">Income Statement</h1>
            <p class="text-gray-600 mt-1">Profit and Loss Statement for <strong class="text-blue-900">Northland Schools Kano</strong></p>
        </div>

        <!-- Date Range Filter -->
        <form method="GET" class="bg-white p-6 rounded-lg shadow-sm border mb-8">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Apply Filter</button>
                <a href="?" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors">Reset</a>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Total Revenue</div>
                <div class="text-2xl font-bold text-green-600 mb-1">₦<?= number_format($total_revenue, 2) ?></div>
                <div class="text-sm text-gray-500"><?= $payment_count ?> transactions</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Total Expenses</div>
                <div class="text-2xl font-bold text-orange-600 mb-1">₦<?= number_format($total_expenses, 2) ?></div>
                <div class="text-sm text-gray-500"><?= $expense_count ?> expenses</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Net Income</div>
                <div class="text-2xl font-bold <?= $net_income >= 0 ? 'text-blue-600' : 'text-red-600' ?> mb-1">₦<?= number_format($net_income, 2) ?></div>
                <div class="text-sm <?= $net_income >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    <i class="fas fa-<?= $net_income >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= $net_income >= 0 ? 'Profit' : 'Loss' ?>
                </div>
            </div>
        </div>

        <!-- Detailed Income Statement -->
        <div class="bg-white rounded-lg shadow-sm border mb-8">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Income Statement Detail</h3>
            </div>
            
            <div class="p-6">
                <table class="w-full">
                    <tbody>
                        <tr class="border-b border-gray-200">
                            <td class="py-4 font-semibold text-green-600">Revenue</td>
                            <td class="py-4 text-right font-semibold text-green-600">₦<?= number_format($total_revenue, 2) ?></td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 pl-8 text-gray-600">Fee Payments</td>
                            <td class="py-3 text-right text-gray-700">₦<?= number_format($total_revenue, 2) ?></td>
                        </tr>
                        
                        <tr class="border-b-2 border-gray-200">
                            <td class="py-5 font-semibold text-orange-600">Less: Expenses</td>
                            <td class="py-5 text-right font-semibold text-orange-600">₦<?= number_format($total_expenses, 2) ?></td>
                        </tr>
                        
                        <?php foreach ($expenses_by_cat as $expense): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 pl-8 text-gray-600"><?= htmlspecialchars($expense['category']) ?></td>
                            <td class="py-2 text-right text-gray-700">₦<?= number_format($expense['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr class="<?= $net_income >= 0 ? 'bg-green-50' : 'bg-red-50' ?>">
                            <td class="py-5 font-bold text-lg <?= $net_income >= 0 ? 'text-green-700' : 'text-red-700' ?>">Net Income (<?= $net_income >= 0 ? 'Profit' : 'Loss' ?>)</td>
                            <td class="py-5 text-right font-bold text-lg <?= $net_income >= 0 ? 'text-green-700' : 'text-red-700' ?>">₦<?= number_format($net_income, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Monthly Revenue Trend -->
        <?php if (!empty($revenue_by_month)): ?>
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Monthly Revenue Trend</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($revenue_by_month as $month_data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-900"><?= date('F Y', strtotime($month_data['month'] . '-01')) ?></td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">₦<?= number_format($month_data['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

</body>
</html>
