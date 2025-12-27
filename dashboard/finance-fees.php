<?php
require_once 'auth-check.php';
checkAuth('admin');
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get fee collection data
$stmt = $conn->query("
    SELECT 
        p.payment_date,
        SUM(p.amount_paid) as daily_total,
        COUNT(p.id) as payment_count
    FROM payments p 
    WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY p.payment_date 
    ORDER BY p.payment_date DESC
");
$daily_collections = $stmt->fetchAll();

// Get total collection
$stmt = $conn->query("SELECT SUM(amount_paid) as total FROM payments");
$total_collection = $stmt->fetch()['total'] ?? 0;
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
            <main class="w-full flex-grow p-8 bg-gradient-to-br from-gray-50 to-blue-50">
                <!-- Page Header with Breadcrumb -->
                <div class="mb-8">
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-home mr-2"></i>
                        <span>Dashboard</span>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                        <span>Finance</span>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                        <span class="text-nsknavy font-semibold">Fee Collection</span>
                    </div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-nsknavy to-nskblue bg-clip-text text-transparent">
                        Fee Collection Report
                    </h1>
                    <p class="text-gray-600 mt-2">Comprehensive overview of fee collections and payment trends</p>
                </div>

                <?php 
                // Calculate statistics
                $total_transactions = count($daily_collections);
                $total_amount = $total_collection;
                $avg_transaction = $total_transactions > 0 ? $total_amount / $total_transactions : 0;
                $recent_7_days = array_slice($daily_collections, 0, 7);
                $recent_total = array_sum(array_column($recent_7_days, 'daily_total'));
                ?>

                <!-- Enhanced Summary Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Collection Card -->
                    <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                        <div class="bg-nskgreen p-4 rounded-full mr-4">
                            <i class="fas fa-money-bill-wave text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">₦<?php echo number_format($total_amount, 2); ?></h3>
                            <p class="text-gray-600 text-sm">Total Collection</p>
                        </div>
                    </div>

                    <!-- Total Transactions Card -->
                    <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                        <div class="bg-nsklightblue p-4 rounded-full mr-4">
                            <i class="fas fa-receipt text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($total_transactions); ?></h3>
                            <p class="text-gray-600 text-sm">Total Transactions</p>
                        </div>
                    </div>

                    <!-- Average Transaction Card -->
                    <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                        <div class="bg-nskgold p-4 rounded-full mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">₦<?php echo number_format($avg_transaction, 2); ?></h3>
                            <p class="text-gray-600 text-sm">Per Transaction</p>
                        </div>
                    </div>

                    <!-- Recent Week Card -->
                    <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                        <div class="bg-nskred p-4 rounded-full mr-4">
                            <i class="fas fa-calendar-week text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">₦<?php echo number_format($recent_total, 2); ?></h3>
                            <p class="text-gray-600 text-sm">Last Week Total</p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Table Section -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                    <!-- Table Header -->
                    <div class="bg-gradient-to-r from-nsknavy to-nskblue px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-white flex items-center">
                                    <i class="fas fa-table mr-3"></i>
                                    Daily Collections Breakdown
                                </h2>
                                <p class="text-blue-100 mt-1 text-sm">Detailed view of last 30 days fee collection activity</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg text-white text-sm font-semibold transition-all duration-200 flex items-center">
                                    <i class="fas fa-download mr-2"></i>
                                    Export
                                </button>
                                <button class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm rounded-lg text-white text-sm font-semibold transition-all duration-200 flex items-center">
                                    <i class="fas fa-print mr-2"></i>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Content -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-alt mr-2 text-nskblue"></i>
                                            Date
                                        </div>
                                    </th>
                                    <th scope="col" class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-invoice mr-2 text-nskblue"></i>
                                            Transactions
                                        </div>
                                    </th>
                                    <th scope="col" class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <i class="fas fa-money-bill-wave mr-2 text-nskblue"></i>
                                            Amount Collected
                                        </div>
                                    </th>
                                    <th scope="col" class="px-8 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <i class="fas fa-chart-bar mr-2 text-nskblue"></i>
                                            Trend
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $prev_amount = 0;
                                foreach ($daily_collections as $index => $row): 
                                    $trend = 0;
                                    if ($index < count($daily_collections) - 1) {
                                        $next_row = $daily_collections[$index + 1];
                                        $trend = (($row['daily_total'] - $next_row['daily_total']) / $next_row['daily_total']) * 100;
                                    }
                                ?>
                                <tr class="hover:bg-blue-50 transition-colors duration-150 group">
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-nskblue to-nsknavy flex items-center justify-center text-white font-bold text-sm">
                                                <?php echo date('d', strtotime($row['payment_date'])); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <?php echo date('l', strtotime($row['payment_date'])); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo date('M Y', strtotime($row['payment_date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md">
                                            <i class="fas fa-receipt mr-2"></i>
                                            <?php echo $row['payment_count']; ?> payments
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="text-lg font-bold text-green-600 flex items-center">
                                            <i class="fas fa-naira-sign mr-1"></i>
                                            <?php echo number_format($row['daily_total'], 2); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            ₦<?php echo number_format($row['daily_total'] / max($row['payment_count'], 1), 2); ?> avg
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <?php if ($trend > 0): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                <i class="fas fa-arrow-up mr-1"></i>
                                                <?php echo number_format(abs($trend), 1); ?>%
                                            </span>
                                        <?php elseif ($trend < 0): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                <i class="fas fa-arrow-down mr-1"></i>
                                                <?php echo number_format(abs($trend), 1); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                <i class="fas fa-minus mr-1"></i>
                                                0%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer with Summary -->
                    <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between text-sm">
                            <div class="text-gray-600">
                                Showing <span class="font-semibold text-gray-900"><?php echo count($daily_collections); ?></span> entries
                            </div>
                            <div class="flex items-center space-x-6">
                                <div class="flex items-center">
                                    <span class="text-gray-600 mr-2">Total:</span>
                                    <span class="text-xl font-bold text-green-600">₦<?php echo number_format($total_amount, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
    </div>
</body>
</html>
