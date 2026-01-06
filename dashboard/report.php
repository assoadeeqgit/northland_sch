<?php
require_once 'auth-check.php';
checkAuth(); // Ensure user is authenticated

// Include spa helper


// Initialize variables
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userRole = ucfirst($_SESSION['user_type'] ?? 'Administrator');

// Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// === DATA FETCHING ===
$stats = [];
$charts = [];
$activities = [];

try {
    // 1. KPI: Total Students (Active)
    $stats['students'] = $db->query("SELECT COUNT(s.id) FROM students s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND s.status = 'active'")->fetchColumn() ?: 0;
    
    // 2. KPI: Total Teachers (Active)
    $stats['teachers'] = $db->query("SELECT COUNT(t.id) FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1")->fetchColumn() ?: 0;
    
    // 3. KPI: Today's Attendance %
    $attendance_today = $db->query("SELECT (SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100 FROM attendance WHERE attendance_date = CURDATE()")->fetchColumn();
    $stats['attendance'] = $attendance_today ? round($attendance_today, 1) . '%' : 'N/A';

    // 4. Get Current Session & Term for Accurate Metrics
    $currSess = $db->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1")->fetchColumn();
    $currTerm = $db->query("SELECT id FROM terms WHERE is_current = 1 AND session_id = " . ($currSess ?: 0) . " LIMIT 1")->fetchColumn();

    // 5. Fees Collected (This Term)
    $feesTermRaw = 0;
    if ($currSess && $currTerm) {
         try {
             $feesColStmt = $db->prepare("SELECT SUM(amount_paid) FROM payments WHERE academic_session_id = ? AND term_id = ?");
             $feesColStmt->execute([$currSess, $currTerm]);
             $feesTermRaw = $feesColStmt->fetchColumn() ?: 0;
             $stats['fees_term'] = number_format($feesTermRaw, 0);
         } catch (Exception $e) { $stats['fees_term'] = '0'; }
    } else {
         $stats['fees_term'] = '0';
    }

    // 6. Pass Rate (Percentage of passed subject entries >= 50%)
    $stats['pass_rate'] = 'N/A';
    if ($currSess && $currTerm) {
        try {
            $passQ = $db->prepare("SELECT 
                (SUM(CASE WHEN total_score >= 50 THEN 1 ELSE 0 END) / COUNT(*)) * 100 
                FROM student_results 
                WHERE session_id = ? AND term_id = ?");
            $passQ->execute([$currSess, $currTerm]);
            $passRateVal = $passQ->fetchColumn();
            if ($passRateVal !== false && $passRateVal !== null) {
                $stats['pass_rate'] = round($passRateVal, 1) . '%';
            } else {
                 $stats['pass_rate'] = 'No Data';
            }
        } catch (Exception $e) { /* Table might not exist */ }
    }

    // 7. Outstanding Fees (Estimated: Total Expected - Total Collected)
    $stats['outstanding'] = '0';
    if ($currSess && $currTerm) {
        try {
            // Fee Structures per class
            $feeStructs = $db->query("SELECT class_id, SUM(amount) as total_class_fee FROM fee_structures WHERE term_id = '$currTerm' GROUP BY class_id")->fetchAll(PDO::FETCH_KEY_PAIR);
            // Student Counts per class
            $classCounts = $db->query("SELECT class_id, COUNT(*) as cnt FROM students WHERE status='active' GROUP BY class_id")->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $totalExpected = 0;
            foreach ($classCounts as $clsId => $cnt) {
                $fee = $feeStructs[$clsId] ?? 0;
                $totalExpected += ($fee * $cnt);
            }
            
            $outstandingRaw = max(0, $totalExpected - $feesTermRaw);
            $stats['outstanding'] = number_format($outstandingRaw, 0);
        } catch (Exception $e) { }
    }

    // --- CHARTS DATA ---

    // Chart 1: Attendance Trend (Last 7 Days)
    $attTrendStmt = $db->query("
        SELECT attendance_date, 
               (SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as rate 
        FROM attendance 
        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY attendance_date 
        ORDER BY attendance_date ASC
    ");
    $attLabels = [];
    $attData = [];
    while ($row = $attTrendStmt->fetch(PDO::FETCH_ASSOC)) {
        $attLabels[] = date('D d', strtotime($row['attendance_date']));
        $attData[] = round($row['rate'], 1);
    }
    // Fill if empty (dummy data for visual if strictly needed, but better to show empty state or single point)
    // If absolutely no data, Chart.js handles empty arrays gracefully.

    // Chart 2: Student Gender Distribution (Male vs Female)
    $genderLabels = ['Male', 'Female'];
    $genderData = [0, 0]; // Default to 0 for both
    
    try {
        // Count male students
        $maleCount = $db->query("
            SELECT COUNT(*) FROM users u 
            JOIN students s ON u.id = s.user_id 
            WHERE u.gender = 'Male' AND s.status = 'active'
        ")->fetchColumn() ?: 0;
        
        // Count female students
        $femaleCount = $db->query("
            SELECT COUNT(*) FROM users u 
            JOIN students s ON u.id = s.user_id 
            WHERE u.gender = 'Female' AND s.status = 'active'
        ")->fetchColumn() ?: 0;
        
        $genderData = [$maleCount, $femaleCount];
    } catch (Exception $e) {
        // If there's an error, keep defaults of [0, 0]
    }

    // Chart 3: Class Population
    $classStmt = $db->query("SELECT c.class_name, COUNT(s.id) as count FROM classes c LEFT JOIN students s ON c.id = s.class_id AND s.status = 'active' GROUP BY c.id ORDER BY c.id");
    $classLabels = [];
    $classData = [];
    while ($row = $classStmt->fetch(PDO::FETCH_ASSOC)) {
        $classLabels[] = $row['class_name'];
        $classData[] = $row['count'];
    }

    // Recent Activity Log
    $logStmt = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");
    $activities = $logStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Handle errors silently or set error message
    $error = "Error loading data: " . $e->getMessage();
}

// === EXPORT HANDLER ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics_summary_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    
    // KPI Section
    fputcsv($output, ['--- KPI Summary ---']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Active Students', $stats['students'] ?? 0]);
    fputcsv($output, ['Total Teachers', $stats['teachers'] ?? 0]);
    fputcsv($output, ['Today\'s Attendance', $stats['attendance'] ?? '0%']);
    fputcsv($output, ['Fees Collected (Month)', $stats['fees'] ?? 0]);
    fputcsv($output, []);

    // Attendance Trend
    fputcsv($output, ['--- Attendance Trend (Last 7 Days) ---']);
    fputcsv($output, ['Date', 'Attendance Rate (%)']);
    foreach ($attLabels as $i => $date) {
        fputcsv($output, [$date, $attData[$i] ?? 0]);
    }
    fputcsv($output, []);

    // Class Population
    fputcsv($output, ['--- Class Population ---']);
    fputcsv($output, ['Class', 'Student Count']);
    foreach ($classLabels as $i => $class) {
        fputcsv($output, [$class, $classData[$i] ?? 0]);
    }

    fclose($output);
    exit();
}

// Check for AJAX request

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Northland Schools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Tailwind Config for Custom Colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',     // Primary Blue
                        nskgreen: '#10b981',    // Success Green
                        nskgold: '#f59e0b',     // Warning/Gold
                        nskred: '#ef4444',      // Danger Red
                        nsknavy: '#1e3a8a',     // Dark Navy
                        nskgray: {
                            50: '#f9fafb',
                            100: '#f3f4f6', 
                            200: '#e5e7eb',
                            400: '#9ca3af',
                            600: '#4b5563',
                            800: '#1f2937'
                        }
                    },
                    fontFamily: {
                        'sans': ['Montserrat', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
        body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }
        .glass-panel { background: white; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    </style>
</head>
<body class="flex bg-nskgray-50">
    <div id="sidebar-container"></div>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content flex-1 min-w-0 overflow-auto">


        <?php 
        $pageTitle = 'Analytics Dashboard';
        $pageSubtitle = 'Real-time overview of school performance';
        require_once 'header.php'; 
        ?>

        <div class="p-6">
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <span class="text-sm text-red-700"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Date Filter (Visual Only for now, simulates functionality) -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-nsknavy">School Overview</h2>
                    <p class="text-nskgray-600">Key performance indicators and trends.</p>
                </div>
                <div class="flex space-x-2">
                    <select class="form-select bg-white border border-nskgray-200 text-nskgray-600 rounded-lg px-4 py-2 focus:outline-none focus:border-nskblue shadow-sm text-sm">
                        <option>This Month</option>
                        <option>Last Month</option>
                        <option>This Term</option>
                        <option>Academic Year</option>
                    </select>
                    <a href="?export=csv" class="bg-white border border-nskgray-200 text-nskgray-600 px-4 py-2 rounded-lg hover:bg-nskgray-50 shadow-sm transition flex items-center">
                        <i class="fas fa-download mr-2"></i> Export
                    </a>
                    <!-- Simulated Professional Feature: Print -->
                    <button onclick="window.print()" class="bg-nskblue text-white px-4 py-2 rounded-lg hover:bg-nsknavy shadow-sm transition">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
                <!-- 1. Total Students -->
                <div class="glass-panel p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300 relative overflow-hidden group">
                     <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-user-graduate text-5xl text-nskblue"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Students</p>
                        <p class="text-2xl font-bold text-nsknavy mt-2"><?= number_format($stats['students']) ?></p>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-500">
                         <span class="text-nskblue bg-blue-50 px-2 py-1 rounded mr-2">Core metric</span> 
                         <span>Affects staffing</span>
                    </div>
                </div>

                <!-- 2. Attendance Rate -->
                <div class="glass-panel p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300 relative overflow-hidden group">
                     <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-chart-line text-5xl text-nskgreen"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Attendance Rate</p>
                        <p class="text-2xl font-bold text-nsknavy mt-2"><?= $stats['attendance'] ?></p>
                    </div>
                    <div class="mt-4 flex items-center text-xs">
                         <span class="text-nskgreen font-bold mr-1"><i class="fas fa-arrow-up"></i> Strong</span> 
                         <span class="text-gray-500">predictor of performance</span>
                    </div>
                </div>

                <!-- 3. Pass Rate -->
                <div class="glass-panel p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300 relative overflow-hidden group">
                     <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-award text-5xl text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pass Rate</p>
                        <p class="text-2xl font-bold text-nsknavy mt-2"><?= $stats['pass_rate'] ?? 'N/A' ?></p>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-500">
                         <span class="text-purple-600 bg-purple-50 px-2 py-1 rounded mr-2">Actionable</span> 
                         <span>Identifies gaps</span>
                    </div>
                </div>

                <!-- 4. Fees Collected -->
                <div class="glass-panel p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300 relative overflow-hidden group">
                     <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-coins text-5xl text-nskgold"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Fees Collected</p>
                        <p class="text-2xl font-bold text-nsknavy mt-2">₦<?= $stats['fees_term'] ?? '0' ?></p>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-500">
                         <span class="text-nskgold bg-yellow-50 px-2 py-1 rounded mr-2">Cash Flow</span> 
                         <span>This term</span>
                    </div>
                </div>
                
                 <!-- 5. Outstanding Fees -->
                <div class="glass-panel p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300 relative overflow-hidden group">
                     <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-exclamation-circle text-5xl text-red-500"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Outstanding Fees</p>
                        <p class="text-2xl font-bold text-nsknavy mt-2">₦<?= $stats['outstanding'] ?? '0' ?></p>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-500">
                         <span class="text-red-500 bg-red-50 px-2 py-1 rounded mr-2">Risk</span> 
                         <span>Unpaid amount</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Attendance Trend (Span 2) -->
                <div class="glass-panel p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Attendance Trend (Last 7 Days)</h3>
                    <div class="relative h-72">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <!-- Gender Distribution (Span 1) -->
                <div class="glass-panel p-6">
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Student Gender Distribution</h3>
                    <div class="relative h-72 flex justify-center">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Class Data & Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Class Population -->
                <div class="glass-panel p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Class Population Overview</h3>
                    <div class="relative h-64">
                         <canvas id="classChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity (Brief) -->
                <div class="glass-panel p-6">
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Recent System Logs</h3>
                    <div class="space-y-4">
                        <?php if(empty($activities)): ?>
                            <p class="text-sm text-nskgray-400 italic">No recent activities.</p>
                        <?php else: ?>
                            <?php foreach($activities as $log): ?>
                                <div class="flex items-start space-x-3">
                                    <div class="w-2 h-2 rounded-full bg-nskblue mt-2 flex-shrink-0"></div>
                                    <div>
                                        <p class="text-sm text-nskgray-800 font-medium line-clamp-1"><?= htmlspecialchars($log['description']) ?></p>
                                        <p class="text-xs text-nskgray-400"><?= date('M d, H:i', strtotime($log['created_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-nskgray-100 text-center">
                        <a href="activity-logs.php" class="text-sm text-nskblue font-semibold hover:text-nsknavy">View All Logs</a>
                    </div>
                </div>
            </div>

        </div>
    
        <?php require_once 'footer.php'; ?>

    <!-- Chart.js Scripts -->
    <script>
        // Colors
        const brandBlue = '#1e40af';
        const brandGreen = '#10b981';
        const brandGold = '#f59e0b';
        const brandRed = '#ef4444';
        const brandPurple = '#8b5cf6';

        // 1. Attendance Chart
        const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctxAtt, {
            type: 'line',
            data: {
                labels: <?= json_encode($attLabels) ?>,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: <?= json_encode($attData) ?>,
                    borderColor: brandBlue,
                    backgroundColor: 'rgba(30, 64, 175, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: brandBlue
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // 2. Gender Chart (Male vs Female)
        const ctxGender = document.getElementById('genderChart').getContext('2d');
        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($genderLabels) ?>,
                datasets: [{
                    data: <?= json_encode($genderData) ?>,
                    backgroundColor: ['#3b82f6', '#ec4899'], // Blue for Male, Pink for Female
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });

        // 3. Class Chart
        const ctxClass = document.getElementById('classChart').getContext('2d');
        new Chart(ctxClass, {
            type: 'bar',
            data: {
                labels: <?= json_encode($classLabels) ?>,
                datasets: [{
                    label: 'Students',
                    data: <?= json_encode($classData) ?>,
                    backgroundColor: brandGreen,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>

    </main>
</body>
</html>
