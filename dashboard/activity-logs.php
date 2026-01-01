<?php
require_once 'auth-check.php';
checkAuth();

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// --- FILTERS & PAGINATION ---
$search = $_GET['search'] ?? '';
$dateFilter = $_GET['date_filter'] ?? ''; // Added Date Filter
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build Query
$whereClause = "WHERE 1=1";
$params = [];

// Date Filter Logic
if (!empty($dateFilter)) {
    $whereClause .= " AND DATE(created_at) = ?";
    $params[] = $dateFilter;
}

// Text Search Logic (Enhanced for Date Strings)
if (!empty($search)) {
    // Search: Desc, User, Action, Dec 01, December 01, Dec 1, 2025-12-21
    $whereClause .= " AND (description LIKE ? OR user_name LIKE ? OR action_type LIKE ? OR DATE_FORMAT(created_at, '%b %d') LIKE ? OR DATE_FORMAT(created_at, '%M %d') LIKE ? OR DATE_FORMAT(created_at, '%b %e') LIKE ? OR created_at LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Get Total for Pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM activity_log $whereClause");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Fetch Logs
$query = "SELECT * FROM activity_log $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for Action Colors
function getActionColor($action) {
    if (stripos($action, 'delete') !== false || stripos($action, 'deactivate') !== false || stripos($action, 'remove') !== false) return 'bg-red-100 text-red-700 border-red-200';
    if (stripos($action, 'create') !== false || stripos($action, 'add') !== false || stripos($action, 'assign') !== false) return 'bg-green-100 text-green-700 border-green-200';
    if (stripos($action, 'update') !== false || stripos($action, 'edit') !== false) return 'bg-orange-100 text-orange-700 border-orange-200';
    if (stripos($action, 'login') !== false) return 'bg-blue-50 text-blue-700 border-blue-100';
    return 'bg-gray-100 text-gray-700 border-gray-200';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Northland Schools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',
                        nsknavy: '#1e3a8a',
                        nskgray: { 50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 800: '#1f2937' }
                    },
                    fontFamily: { 'sans': ['Montserrat', 'system-ui', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out'
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="sidebar.css">
    <style>@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap'); body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="flex bg-nskgray-50">
    <?php require_once 'sidebar.php'; ?>
    <main class="main-content flex-1 min-w-0 overflow-auto h-screen">
        <?php 
        $pageTitle = 'Activity Logs';
        $pageSubtitle = 'Real-time system audit trail';
        require_once 'header.php'; 
        ?>

        <div class="p-6 animate-fade-in">
            <!-- Search & Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="w-full md:w-auto flex-grow max-w-3xl">
                    <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full">
                        <div class="relative flex-grow">
                            <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search logs (e.g. user, action, 'Dec 21')..." 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-nskblue focus:ring-2 focus:ring-nskblue/20 transition-all outline-none text-sm text-gray-700">
                        </div>
                        <div class="relative w-full sm:w-48 flex-shrink-0">
                            <input type="date" name="date_filter" value="<?= htmlspecialchars($dateFilter) ?>" 
                                   class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:border-nskblue focus:ring-2 focus:ring-nskblue/20 outline-none text-sm text-gray-700 cursor-pointer shadow-sm"
                                   onchange="this.form.submit()" title="Filter by specific date (Select to apply)">
                        </div>
                        <?php if($search || $dateFilter): ?>
                            <a href="activity-logs.php" class="px-3 py-3 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition flex items-center justify-center" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="text-sm text-gray-500 whitespace-nowrap">
                    Total Records: <span class="font-bold text-nsknavy"><?= number_format($totalLogs) ?></span>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden animate-slide-up">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Details</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fas fa-search text-3xl mb-3 opacity-50"></i>
                                        <p class="text-sm">No activity logs found matching your criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): 
                                    $actionColor = getActionColor($log['action_type']);
                                ?>
                                <tr class="hover:bg-blue-50/30 transition-colors duration-200 group">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-700"><?= date('M d, Y', strtotime($log['created_at'])) ?></span>
                                            <span class="text-xs text-gray-400"><?= date('h:i A', strtotime($log['created_at'])) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-nskblue/10 flex items-center justify-center text-nskblue font-bold text-xs mr-3">
                                                <?= strtoupper(substr($log['user_name'], 0, 1)) ?>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-700 text-capitalize"><?= htmlspecialchars($log['user_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border <?= $actionColor ?>">
                                            <?= htmlspecialchars($log['action_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400 font-mono">
                                        <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (Matching User Management Style) -->
                <?php if ($totalPages > 1): ?>
                <div class="flex flex-col sm:flex-row justify-between items-center py-4 px-6 border-t bg-gray-50/50">
                    <div class="text-sm text-gray-600 mb-2 sm:mb-0">
                         Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $limit, $totalLogs) ?></span> of <span class="font-medium"><?= $totalLogs ?></span> entries
                    </div>
                    <div class="flex space-x-1">
                         <!-- Params for links -->
                         <?php $linkParams = "&search=" . urlencode($search) . "&date_filter=" . urlencode($dateFilter); ?>
                         
                         <!-- Previous -->
                         <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $linkParams ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 bg-white flex items-center text-gray-700 transition">
                                <i class="fas fa-chevron-left mr-1"></i> Prev
                            </a>
                         <?php else: ?>
                            <button disabled class="px-3 py-1 border border-gray-200 rounded text-sm text-gray-300 bg-gray-50 cursor-not-allowed flex items-center">
                                <i class="fas fa-chevron-left mr-1"></i> Prev
                            </button>
                         <?php endif; ?>
                         
                         <!-- Numbers Logic -->
                         <?php
                         $start = max(1, $page - 2);
                         $end = min($totalPages, $page + 2);
                         
                         // First Page
                         if ($start > 1) { 
                             echo '<a href="?page=1'.$linkParams.'" class="px-3 py-1 border border-gray-300 rounded text-sm bg-white hover:bg-gray-100 text-gray-700 transition">1</a>';
                             if ($start > 2) echo '<span class="px-2 text-gray-400">...</span>';
                         }
                         
                         // Middle Pages
                         for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?page=<?= $i ?><?= $linkParams ?>" class="px-3 py-1 border rounded text-sm transition <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-gray-100 text-gray-700' ?>">
                                <?= $i ?>
                            </a>
                         <?php endfor; 
                         
                         // Last Page
                         if ($end < $totalPages) { 
                             if ($end < $totalPages - 1) echo '<span class="px-2 text-gray-400">...</span>';
                             echo '<a href="?page='.$totalPages.$linkParams.'" class="px-3 py-1 border border-gray-300 rounded text-sm bg-white hover:bg-gray-100 text-gray-700 transition">' . $totalPages . '</a>';
                         }
                         ?>
                         
                         <!-- Next -->
                         <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $linkParams ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-100 bg-white flex items-center text-gray-700 transition">
                                Next <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                         <?php else: ?>
                            <button disabled class="px-3 py-1 border border-gray-200 rounded text-sm text-gray-300 bg-gray-50 cursor-not-allowed flex items-center">
                                Next <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                         <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
