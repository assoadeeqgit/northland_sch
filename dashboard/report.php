<?php
require_once 'auth-check.php';
checkAuth(); // Ensure user is authenticated

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

    // 4. KPI: Fees Collected (This Month)
    $stats['fees'] = 0;
    try {
        $fees_query = $db->prepare("SELECT SUM(amount_paid) FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
        $fees_query->execute();
        $stats['fees'] = number_format($fees_query->fetchColumn() ?: 0, 0); // Round to integer for display
    } catch (Exception $e) {
        // Table might not exist or error
        $stats['fees'] = '0';
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

    // Chart 2: Student Gender Distribution
    $genderStmt = $db->query("SELECT gender, COUNT(*) as count FROM student_profiles GROUP BY gender");
    $genderLabels = [];
    $genderData = [];
    while ($row = $genderStmt->fetch(PDO::FETCH_ASSOC)) {
        $l = ucfirst($row['gender'] ?: 'Unknown');
        $genderLabels[] = $l;
        $genderData[] = $row['count'];
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Students -->
                <div class="glass-panel p-5 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
                    <div>
                        <p class="text-sm font-medium text-nskgray-600">Active Students</p>
                        <p class="text-3xl font-bold text-nsknavy mt-1"><?= number_format($stats['students']) ?></p>
                        <p class="text-xs text-nskgreen mt-1 font-medium"><i class="fas fa-arrow-up mr-1"></i> Current Enrollment</p>
                    </div>
                    <div class="stat-icon bg-blue-50 text-nskblue">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <!-- Teachers -->
                <div class="glass-panel p-5 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
                    <div>
                        <p class="text-sm font-medium text-nskgray-600">Total Teachers</p>
                        <p class="text-3xl font-bold text-nsknavy mt-1"><?= number_format($stats['teachers']) ?></p>
                        <p class="text-xs text-nskblue mt-1 font-medium">Active Staff</p>
                    </div>
                    <div class="stat-icon bg-green-50 text-nskgreen">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>

                <!-- Attendance -->
                <div class="glass-panel p-5 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
                    <div>
                        <p class="text-sm font-medium text-nskgray-600">Attendance (Today)</p>
                        <p class="text-3xl font-bold text-nsknavy mt-1"><?= $stats['attendance'] ?></p>
                        <p class="text-xs text-nskgold mt-1 font-medium">Daily Average</p>
                    </div>
                    <div class="stat-icon bg-yellow-50 text-nskgold">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>

                <!-- Fees -->
                <div class="glass-panel p-5 flex items-center justify-between hover:shadow-lg transition-shadow duration-300">
                    <div>
                        <p class="text-sm font-medium text-nskgray-600">Fees (This Month)</p>
                        <p class="text-3xl font-bold text-nsknavy mt-1">₦<?= $stats['fees'] ?></p>
                        <p class="text-xs text-nskgray-400 mt-1 font-medium">Collections</p>
                    </div>
                    <div class="stat-icon bg-purple-50 text-purple-600">
                        <i class="fas fa-coins"></i>
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
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Student Demographics</h3>
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
    </main>

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

        // 2. Gender Chart
        const ctxGender = document.getElementById('genderChart').getContext('2d');
        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($genderLabels) ?>,
                datasets: [{
                    data: <?= json_encode($genderData) ?>,
                    backgroundColor: [brandBlue, brandPurple, brandGold], // Blue (Male), Purple (Female etc)
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
</body>
</html>