<?php
require_once 'auth-check.php';
checkAuth('admin');
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get income data
$stmt = $conn->query("SELECT SUM(amount_paid) as total_income FROM payments");
$total_income = $stmt->fetch()['total_income'] ?? 0;

// Get expenses
$stmt = $conn->query("SELECT SUM(amount) as total_expenses FROM expenses");
$total_expenses = $stmt->fetch()['total_expenses'] ?? 0;

$net_income = $total_income - $total_expenses;

// Monthly breakdown
$stmt = $conn->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount_paid) as monthly_income
    FROM payments 
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month DESC
");
$monthly_income = $stmt->fetchAll();
?>

<?php include 'head.php'; ?>

<style>
.main-content-wrapper {
    margin-left: 250px;
    width: calc(100% - 250px);
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .main-content-wrapper {
        margin-left: 0;
        width: 100%;
    }
}

.bg-nskblue { background-color: #1e40af; }
.bg-nsklightblue { background-color: #3b82f6; }
.bg-nsknavy { background-color: #1e3a8a; }
.bg-nskgold { background-color: #f59e0b; }
.bg-nskgreen { background-color: #10b981; }
.bg-nskred { background-color: #ef4444; }

.dashboard-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
}
</style>

<body class="bg-gray-50 font-sans">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Wrapper with proper margin -->
    <div class="main-content-wrapper">
        <!-- Top Header -->
        <?php include 'header.php'; ?>

            <!-- Main Content -->
            <main class="w-full flex-grow p-6">
        <h1 class="text-3xl font-bold text-nsknavy mb-8">Income Statement</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="dashboard-card bg-white rounded-xl shadow-md p-6 flex items-center">
                <div class="bg-nskgreen p-4 rounded-full mr-4">
                    <i class="fas fa-arrow-up text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">₦<?php echo number_format($total_income, 2); ?></h3>
                    <p class="text-gray-600">Total Income</p>
                </div>
            </div>
            <div class="dashboard-card bg-white rounded-xl shadow-md p-6 flex items-center">
                <div class="bg-nskred p-4 rounded-full mr-4">
                    <i class="fas fa-arrow-down text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">₦<?php echo number_format($total_expenses, 2); ?></h3>
                    <p class="text-gray-600">Total Expenses</p>
                </div>
            </div>
            <div class="dashboard-card bg-white rounded-xl shadow-md p-6 flex items-center">
                <div class="bg-nsklightblue p-4 rounded-full mr-4">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold <?php echo $net_income >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        ₦<?php echo number_format($net_income, 2); ?>
                    </h3>
                    <p class="text-gray-600">Net Income</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-nsknavy mb-6">Monthly Income Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($monthly_income as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('F Y', strtotime($row['month'] . '-01')); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                ₦<?php echo number_format($row['monthly_income'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
            </main>
    </div>
</body>
</html>
