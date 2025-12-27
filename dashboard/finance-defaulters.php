<?php
require_once 'auth-check.php';
checkAuth('admin');
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get search parameters
$search_class = $_GET['class'] ?? '';
$payment_status = $_GET['status'] ?? 'all';
$search_name = $_GET['name'] ?? '';

// Build query based on filters
$where_conditions = [];
$params = [];

if (!empty($search_class)) {
    $where_conditions[] = "c.class_name LIKE ?";
    $params[] = "%$search_class%";
}

if (!empty($search_name)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.admission_number LIKE ?)";
    $params[] = "%$search_name%";
    $params[] = "%$search_name%";
    $params[] = "%$search_name%";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get students with payment info
$stmt = $conn->prepare("
    SELECT 
        u.first_name,
        u.last_name,
        s.admission_number,
        c.class_name,
        COALESCE(SUM(p.amount_paid), 0) as paid_amount,
        COUNT(p.id) as payment_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN payments p ON s.id = p.student_id
    $where_clause
    GROUP BY s.id, u.first_name, u.last_name, s.admission_number, c.class_name
    ORDER BY paid_amount ASC
");

$stmt->execute($params);
$all_students = $stmt->fetchAll();

// Filter by payment status
if ($payment_status === 'paid') {
    $students = array_filter($all_students, function($student) {
        return $student['paid_amount'] >= 50000;
    });
} elseif ($payment_status === 'unpaid') {
    $students = array_filter($all_students, function($student) {
        return $student['paid_amount'] < 50000;
    });
} else {
    $students = $all_students;
}

// Get all classes for dropdown
$classes_stmt = $conn->query("SELECT DISTINCT class_name FROM classes ORDER BY class_name");
$classes = $classes_stmt->fetchAll();
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
.text-nsknavy { color: #1e3a8a; }

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
        <h1 class="text-3xl font-bold text-nsknavy mb-8">Defaulters List</h1>
        
        <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center mb-6">
                <div class="bg-nskred p-4 rounded-full mr-4">
                    <i class="fas fa-search text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-nsknavy">Student Payment Search</h3>
                    <p class="text-gray-600">Search students by class and payment status</p>
                </div>
            </div>
            
            <!-- Search Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student Name/ID</label>
                    <input type="text" id="search-name" placeholder="Search name or admission number" 
                           value="<?php echo htmlspecialchars($search_name); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select id="search-class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['class_name']); ?>" 
                                    <?php echo $search_class === $class['class_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                    <select id="payment-status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue">
                        <option value="all" <?php echo $payment_status === 'all' ? 'selected' : ''; ?>>All Students</option>
                        <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>Paid (≥₦50,000)</option>
                        <option value="unpaid" <?php echo $payment_status === 'unpaid' ? 'selected' : ''; ?>>Unpaid (<₦50,000)</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="clearFilters()" class="w-full px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">
                        Clear Filters
                    </button>
                </div>
            </div>
            
            <!-- Results Summary -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <span class="text-sm text-gray-600">Showing <strong><?php echo count($students); ?></strong> students</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payments</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($students as $student): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($student['admission_number']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                <span class="<?php echo $student['paid_amount'] >= 50000 ? 'text-green-600' : 'text-red-600'; ?>">
                                    ₦<?php echo number_format($student['paid_amount'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($student['paid_amount'] >= 50000): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i> Paid
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Outstanding
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?php echo $student['payment_count']; ?>
                                </span>
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

<script>
// Live search functionality
function performSearch() {
    const name = document.getElementById('search-name').value;
    const className = document.getElementById('search-class').value;
    const status = document.getElementById('payment-status').value;
    
    const params = new URLSearchParams();
    if (name) params.append('name', name);
    if (className) params.append('class', className);
    if (status) params.append('status', status);
    
    window.location.href = '?' + params.toString();
}

function clearFilters() {
    window.location.href = window.location.pathname;
}

// Add event listeners for live search
document.getElementById('search-name').addEventListener('input', debounce(performSearch, 500));
document.getElementById('search-class').addEventListener('change', performSearch);
document.getElementById('payment-status').addEventListener('change', performSearch);

// Debounce function to limit API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

</html>
