<?php
require_once 'auth-check.php';
checkAuth(); // Ensure user is authenticated

// Initialize variables
$stats = []; // This will be filled by dynamic KPIs now
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userRole = ucfirst($_SESSION['user_type'] ?? 'Administrator');

// Create initials
$nameParts = explode(' ', $userName, 2);
$firstInitial = $nameParts[0][0] ?? 'A';
$lastInitial = isset($nameParts[1]) ? ($nameParts[1][0] ?? '') : '';
$userInitial = strtoupper($firstInitial . $lastInitial);
$generatedReports = []; // Initialize as empty
$recentActivities = []; // Initialize as empty
$reportFilter = $_GET['tab'] ?? 'all'; // Get the filter tab

// Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    // Create necessary tables if they don't exist
    createReportTables($db);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to create necessary tables
function createReportTables($db)
{
    try {
        // Create generated_reports table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS generated_reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_name VARCHAR(255) NOT NULL,
                report_type VARCHAR(100) NOT NULL,
                period_start DATE NULL,
                period_end DATE NULL,
                file_path VARCHAR(500) NOT NULL,
                generated_by VARCHAR(255) NOT NULL,
                generated_date DATETIME NOT NULL,
                download_count INT DEFAULT 0
            )
        ");

        // Create report_schedules table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS report_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_type VARCHAR(100) NOT NULL,
                frequency VARCHAR(50) NOT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                created_by VARCHAR(255) NOT NULL,
                created_date DATETIME NOT NULL,
                is_active BOOLEAN DEFAULT TRUE
            )
        ");

        // *** FIXED: Updated activity_log table to match your structure ***
        $db->exec("
            CREATE TABLE IF NOT EXISTS activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_name VARCHAR(255) NOT NULL,
                action_type VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                icon VARCHAR(50) DEFAULT 'fas fa-info-circle',
                color VARCHAR(50) DEFAULT 'bg-nskblue',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch (Exception $e) {
        error_log("Table creation error: " . $e->getMessage());
    }
}

// *** FIXED: Updated logActivity function to match your table ***
function logActivity($db, $performedBy, $actionType, $description, $icon = 'fas fa-info-circle', $iconBg = 'bg-nskblue')
{
    try {
        // Note: $iconBg is mapped to the 'color' column in your DB
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_name, action_type, description, icon, color, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$performedBy, $actionType, $description, $icon, $iconBg]);
        return true;
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
        return false;
    }
}

// === POST HANDLERS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_report'])) {
        generateReport($db);
    }
    if (isset($_POST['schedule_report'])) {
        scheduleReport($db);
    }
    if (isset($_POST['delete_report'])) {
        deleteReport($db, $_POST['report_id']);
    }
}

// === HELPER FUNCTIONS ===
function generateReport($db)
{
    try {
        $reportType = $_POST['report_type'] ?? '';
        $reportPeriod = $_POST['report_period'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reportFormat = $_POST['report_format'] ?? 'pdf';

        if (empty($reportType) || empty($reportPeriod)) {
            throw new Exception("Report type and period are required.");
        }

        $timestamp = date('Ymd_His');
        $filename = "report_{$reportType}_{$timestamp}.{$reportFormat}";

        $stmt = $db->prepare("
            INSERT INTO generated_reports (report_name, report_type, period_start, period_end, file_path, generated_by, generated_date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $reportName = ucfirst($reportType) . " Report - " . ucfirst($reportPeriod);
        $stmt->execute([
            $reportName,
            $reportType,
            $startDate ?: null,
            $endDate ?: null,
            $filename,
            $_SESSION['user_name'] ?? 'Admin'
        ]);

        $_SESSION['success'] = "Report generated successfully!";
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity($db, $admin_name, "Report Generated", "Generated: $reportName", "fas fa-file-pdf", "bg-nskgreen");

        header("Location: report.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error generating report: " . $e->getMessage();
        header("Location: report.php");
        exit();
    }
}

function scheduleReport($db)
{
    try {
        $reportType = $_POST['scheduled_report_type'] ?? '';
        $frequency = $_POST['schedule_frequency'] ?? '';
        $recipientEmail = $_POST['recipient_email'] ?? '';

        if (empty($reportType) || empty($frequency) || empty($recipientEmail)) {
            throw new Exception("All fields are required for scheduling.");
        }

        $stmt = $db->prepare("
            INSERT INTO report_schedules (report_type, frequency, recipient_email, created_by, created_date) 
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $reportType,
            $frequency,
            $recipientEmail,
            $_SESSION['user_name'] ?? 'Admin'
        ]);

        $_SESSION['success'] = "Report scheduled successfully!";
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity($db, $admin_name, "Report Scheduled", "Scheduled $reportType report ($frequency)", "fas fa-clock", "bg-nskgold");

        header("Location: report.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error scheduling report: " . $e->getMessage();
        header("Location: report.php");
        exit();
    }
}

function deleteReport($db, $reportId)
{
    try {
        $stmt = $db->prepare("SELECT report_name FROM generated_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $reportName = $stmt->fetchColumn();

        $stmt = $db->prepare("DELETE FROM generated_reports WHERE id = ?");
        $stmt->execute([$reportId]);

        $_SESSION['success'] = "Report deleted successfully!";
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity($db, $admin_name, "Report Deleted", "Deleted report: $reportName (ID: $reportId)", "fas fa-trash", "bg-nskred");

        header("Location: report.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting report: " . $e->getMessage();
        header("Location: report.php");
        exit();
    }
}

// === DATA FETCHING FOR PAGE LOAD ===
try {
    // === 1. Fetch Professional KPI Stats (NEW) ===
    $stats['kpi_students'] = $db->query("SELECT COUNT(s.id) FROM students s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1")->fetchColumn() ?: 0;
    $stats['kpi_teachers'] = $db->query("SELECT COUNT(t.id) FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1")->fetchColumn() ?: 0;
    $stats['kpi_classes'] = $db->query("SELECT COUNT(*) FROM classes")->fetchColumn() ?: 0;

    // Calculate Today's Attendance
    $attendance_today_raw = $db->query("SELECT (SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100 FROM attendance WHERE attendance_date = CURDATE()")->fetchColumn();
    $stats['kpi_attendance'] = $attendance_today_raw ? round($attendance_today_raw, 1) . '%' : 'N/A';


    // === 2. Fetch Data for Charts ===
    // Report types distribution data
    $reportTypeStmt = $db->query("SELECT report_type, COUNT(*) as count FROM generated_reports GROUP BY report_type");
    $reportTypeLabels = [];
    $reportTypeCounts = [];
    while ($row = $reportTypeStmt->fetch(PDO::FETCH_ASSOC)) {
        $reportTypeLabels[] = ucfirst($row['report_type']);
        $reportTypeCounts[] = $row['count'];
    }

    if (empty($reportTypeLabels)) {
        $reportTypeLabels = ['Academic', 'Attendance', 'Financial'];
        $reportTypeCounts = [0, 0, 0];
    }
    $reportTypeLabelsJS = json_encode($reportTypeLabels);
    $reportTypeCountsJS = json_encode($reportTypeCounts);

    // Monthly report generation trend
    $monthlyStmt = $db->query("
        SELECT DATE_FORMAT(generated_date, '%Y-%m') as month, COUNT(id) as count 
        FROM generated_reports 
        WHERE generated_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month ASC
        LIMIT 12
    ");
    $monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    $monthlyLabels = [];
    $monthlyCounts = [];
    $monthMap = [];

    // Initialize last 12 months with 0 counts
    for ($i = 11; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime($monthKey . '-01'));
        $monthMap[$monthKey] = 0;
        $monthlyLabels[] = $monthLabel;
    }
    // Populate with real data
    foreach ($monthlyData as $data) {
        if (isset($monthMap[$data['month']])) {
            $monthMap[$data['month']] = (int) $data['count'];
        }
    }
    $monthlyCounts = array_values($monthMap);

    $monthlyLabelsJS = json_encode($monthlyLabels);
    $monthlyCountsJS = json_encode($monthlyCounts);


    // === 3. Fetch Generated Reports Data ===
    $whereClause = "";
    $params = [];
    if ($reportFilter !== 'all') {
        $whereClause = "WHERE report_type = ?";
        $params[] = $reportFilter;
    }

    $reportsStmt = $db->prepare("
        SELECT id, report_name, report_type, generated_date, period_start, period_end, download_count, file_path, generated_by 
        FROM generated_reports 
        $whereClause
        ORDER BY generated_date DESC 
        LIMIT 10
    ");
    $reportsStmt->execute($params);
    $generatedReports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);


    // === 4. Fetch Recent Activities (Paginated) ===
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($page < 1)
        $page = 1;
    $limit = 5; // Display 5 activities per page
    $offset = ($page - 1) * $limit;

    $totalLogsStmt = $db->query("SELECT COUNT(*) FROM activity_log");
    $totalLogs = $totalLogsStmt ? $totalLogsStmt->fetchColumn() : 0;
    $totalPages = $totalLogs > 0 ? ceil($totalLogs / $limit) : 1;

    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    // *** FIXED: Query now matches your DB schema ***
    $logsStmt = $db->prepare("
        SELECT user_name, action_type, description, icon, color, created_at 
        FROM activity_log 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $logsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $logsStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $logsStmt->execute();
    $recentActivities = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare query params for pagination links
    $paginationParams = $_GET;
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching report data: " . $e->getMessage();
    error_log("Report data error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="sidebar.css">
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
                        nskred: '#ef4444',
                        nskgray: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827'
                        }
                    },
                    fontFamily: {
                        'sans': ['Montserrat', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8fafc;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card {
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .report-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .report-table tr:last-child td {
            border-bottom: 0;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .tab-button {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .tab-button::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background-color: #1e40af;
            transition: width 0.3s ease;
        }

        .tab-button.active::after {
            width: 100%;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.7);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .chart-container {
            position: relative;
            height: 300px;
            /* Made charts taller */
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(30, 64, 175, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            transition: all 0.3s ease;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
        }

        .pagination-btn {
            transition: all 0.2s ease;
        }

        .pagination-btn:hover:not(.disabled) {
            background-color: #1e40af;
            color: white;
        }

        .sidebar {
            transition: all 0.3s ease;
            width: 250px;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .main-content {
            transition: all 0.3s ease;
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .main-content.expanded {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .sidebar.mobile-show {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loading-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #1e40af;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        /* Unified stat icon circle used across dashboard */
        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.06);
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="flex bg-nskgray-50">
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content flex-1">
        <?php
        $pageTitle = 'Reports & Analytics';
        $pageSubtitle = 'Generate and manage institutional reports';
        require_once 'header.php';
        ?>

        <div class="p-6 fade-in">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= $_SESSION['success'];
                            unset($_SESSION['success']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?= $_SESSION['error'];
                            unset($_SESSION['error']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-white rounded-xl shadow-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-nskgray-800 text-sm font-medium">Active Students</p>
                            <p class="text-3xl font-bold text-nsknavy mt-2"><?= $stats['kpi_students'] ?></p>
                            <p class="text-xs text-nskblue mt-1 flex items-center">
                                Total enrolled
                            </p>
                        </div>
                        <div class="icon-circle bg-nskblue bg-opacity-10">
                            <i class="fas fa-user-graduate text-nskblue text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl shadow-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-nskgray-800 text-sm font-medium">Teaching Staff</p>
                            <p class="text-3xl font-bold text-nsknavy mt-2"><?= $stats['kpi_teachers'] ?></p>
                            <p class="text-xs text-nskgreen mt-1 flex items-center">
                                Active educators
                            </p>
                        </div>
                        <div class="icon-circle bg-nskgreen bg-opacity-10">
                            <i class="fas fa-chalkboard-teacher text-nskgreen text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl shadow-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-nskgray-800 text-sm font-medium">Today's Attendance</p>
                            <p class="text-3xl font-bold text-nsknavy mt-2"><?= $stats['kpi_attendance'] ?></p>
                            <p class="text-xs text-nskgold mt-1 flex items-center">
                                Student present rate
                            </p>
                        </div>
                        <div class="icon-circle bg-nskgold bg-opacity-10">
                            <i class="fas fa-calendar-check text-nskgold text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-xl shadow-soft p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-nskgray-800 text-sm font-medium">Active Classes</p>
                            <p class="text-3xl font-bold text-nsknavy mt-2"><?= $stats['kpi_classes'] ?></p>
                            <p class="text-xs text-nskred mt-1 flex items-center">
                                Total classrooms
                            </p>
                        </div>
                        <div class="icon-circle bg-nskred bg-opacity-10">
                            <i class="fas fa-school text-nskred text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-soft p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">Report Center</h2>
                        <p class="text-nskgray-600 mt-1">Generate and schedule institutional reports</p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button id="generateReportBtn"
                            class="btn-success text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Generate Report
                        </button>

                        <button id="scheduleReportBtn"
                            class="btn-warning text-white px-5 py-2.5 rounded-lg font-semibold transition flex items-center">
                            <i class="fas fa-clock mr-2"></i> Schedule
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-soft p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-nsknavy">Generated Reports</h2>

                    <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
                        <a href="report.php?tab=all"
                            class="tab-button px-4 py-2 rounded-lg font-medium <?= $reportFilter == 'all' ? 'active text-nskblue font-semibold' : 'text-nskgray-700 hover:text-nskblue' ?>">
                            All Reports
                        </a>
                        <a href="report.php?tab=academic"
                            class="tab-button px-4 py-2 rounded-lg font-medium <?= $reportFilter == 'academic' ? 'active text-nskblue font-semibold' : 'text-nskgray-700 hover:text-nskblue' ?>">
                            Academic
                        </a>
                        <a href="report.php?tab=attendance"
                            class="tab-button px-4 py-2 rounded-lg font-medium <?= $reportFilter == 'attendance' ? 'active text-nskblue font-semibold' : 'text-nskgray-700 hover:text-nskblue' ?>">
                            Attendance
                        </a>
                        <a href="report.php?tab=financial"
                            class="tab-button px-4 py-2 rounded-lg font-medium <?= $reportFilter == 'financial' ? 'active text-nskblue font-semibold' : 'text-nskgray-700 hover:text-nskblue' ?>">
                            Financial
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-nskgray-200">
                    <table class="min-w-full report-table">
                        <thead>
                            <tr>
                                <th class="py-4 px-6 text-left text-nsknavy font-semibold">Report Name</th>
                                <th class="py-4 px-6 text-left text-nsknavy font-semibold">Type</th>
                                <th class="py-4 px-6 text-left text-nsknavy font-semibold">Generated On</th>
                                <th class="py-4 px-6 text-left text-nsknavy font-semibold">Period</th>
                                <th class="py-4 px-6 text-left text-nsknavy font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-nskgray-100">
                            <?php if (empty($generatedReports)): ?>
                                <tr>
                                    <td colspan="5" class="py-12 px-6 text-center text-nskgray-500">
                                        <i class="fas fa-file-alt text-4xl mb-4 text-nskgray-300"></i>
                                        <p class="text-lg font-medium">No reports found</p>
                                        <p class="text-sm mt-1">Generate a new report to get started</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($generatedReports as $report): ?>
                                    <tr class="hover:bg-nskgray-50 transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-gradient-to-r from-nskblue to-nsklightblue flex items-center justify-center text-white font-bold mr-3">
                                                    <i class="fas fa-file-pdf"></i>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-nskgray-900">
                                                        <?= htmlspecialchars($report['report_name']) ?>
                                                    </p>
                                                    <p class="text-sm text-nskgray-600">Generated by:
                                                        <?= htmlspecialchars($report['generated_by']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php
                                            $typeColors = [
                                                'academic' => 'bg-blue-100 text-blue-800',
                                                'attendance' => 'bg-green-100 text-green-800',
                                                'financial' => 'bg-amber-100 text-amber-800',
                                                'demographic' => 'bg-purple-100 text-purple-800'
                                            ];
                                            $typeColor = $typeColors[$report['report_type']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span
                                                class="status-badge <?= $typeColor ?>"><?= htmlspecialchars(ucfirst($report['report_type'])) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <p class="text-sm font-medium text-nskgray-900">
                                                <?= date('d M Y', strtotime($report['generated_date'])) ?>
                                            </p>
                                            <p class="text-xs text-nskgray-600">
                                                <?= date('h:i A', strtotime($report['generated_date'])) ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <p class="text-sm text-nskgray-900">
                                                <?php if ($report['period_start'] && $report['period_end']): ?>
                                                    <?= date('d M Y', strtotime($report['period_start'])) ?> -
                                                    <?= date('d M Y', strtotime($report['period_end'])) ?>
                                                <?php else: ?>
                                                    <span class="text-nskgray-500">N/A</span>
                                                <?php endif; ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex space-x-2">
                                                <button
                                                    class="text-nskblue hover:bg-blue-50 p-2 rounded-lg transition-colors view-report"
                                                    data-report-id="<?= $report['id'] ?>" title="View (simulated)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button
                                                    class="text-nskgreen hover:bg-green-50 p-2 rounded-lg transition-colors download-report"
                                                    data-filename="<?= $report['file_path'] ?>" title="Download (simulated)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <form method="POST" action="" class="inline">
                                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                    <button type="submit" name="delete_report"
                                                        class="text-nskred hover:bg-red-50 p-2 rounded-lg transition-colors delete-report"
                                                        onclick="return confirm('Are you sure you want to delete this report?')"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-1 bg-white rounded-xl shadow-soft p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-nsknavy">Recent Activities</h2>
                        <span class="text-xs bg-nskblue text-white px-2 py-1 rounded-full"><?= $totalLogs ?>
                            total</span>
                    </div>

                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center text-nskgray-500 py-6">
                                <i class="fas fa-history text-4xl mb-3 text-nskgray-300"></i>
                                <p>No activities found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="flex items-start space-x-3 pb-4 border-b border-nskgray-100 last:border-b-0">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center <?= htmlspecialchars($activity['color']) ?> text-white">
                                        <i class="fas <?= htmlspecialchars($activity['icon']) ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-nskgray-800 truncate">
                                            <?= htmlspecialchars($activity['description']) ?>
                                        </p>
                                        <p class="text-xs text-nskgray-500 mt-1">
                                            <?php if (!empty($activity['user_name'])): ?>
                                                By: <strong><?= htmlspecialchars($activity['user_name']) ?></strong> •
                                            <?php endif; ?>
                                            <?= date('d M, h:i A', strtotime($activity['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-between items-center mt-6 pt-4 border-t border-nskgray-200">
                            <span class="text-sm text-nskgray-600">
                                Page <strong class="text-nsknavy"><?= $page ?></strong> of <strong
                                    class="text-nsknavy"><?= $totalPages ?></strong>
                            </span>

                            <div class="flex items-center space-x-1">
                                <?php if ($page > 1): ?>
                                    <?php $paginationParams['page'] = $page - 1; ?>
                                    <a href="report.php?<?= http_build_query($paginationParams) ?>"
                                        class="pagination-btn inline-flex items-center px-3 py-1 border border-nskgray-300 rounded text-sm font-medium text-nskgray-700 bg-white">
                                        <i class="fas fa-chevron-left mr-1 text-xs"></i>
                                        Prev
                                    </a>
                                <?php else: ?>
                                    <span
                                        class="pagination-btn inline-flex items-center px-3 py-1 border border-nskgray-200 rounded text-sm font-medium text-nskgray-400 bg-nskgray-50 cursor-not-allowed">
                                        <i class="fas fa-chevron-left mr-1 text-xs"></i>
                                        Prev
                                    </span>
                                <?php endif; ?>

                                <?php if ($page < $totalPages): ?>
                                    <?php $paginationParams['page'] = $page + 1; ?>
                                    <a href="report.php?<?= http_build_query($paginationParams) ?>"
                                        class="pagination-btn inline-flex items-center px-3 py-1 border border-nskgray-300 rounded text-sm font-medium text-nskgray-700 bg-white">
                                        Next
                                        <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                <?php else: ?>
                                    <span
                                        class="pagination-btn inline-flex items-center px-3 py-1 border border-nskgray-200 rounded text-sm font-medium text-nskgray-400 bg-nskgray-50 cursor-not-allowed">
                                        Next
                                        <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-2 grid grid-cols-1 gap-6">
                    <div class="bg-white rounded-xl shadow-soft p-6">
                        <h2 class="text-xl font-bold text-nsknavy mb-4">Report Generation Trend</h2>
                        <div class="chart-container">
                            <canvas id="reportTrendsChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-soft p-6">
                        <h2 class="text-xl font-bold text-nsknavy mb-4">Report Type Distribution</h2>
                        <div class="chart-container">
                            <canvas id="reportTypesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="generateReportModal" class="modal">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Generate New Report</h3>
                    <button
                        class="close-modal text-nskgray-500 hover:text-nskgray-700 p-1 rounded-full hover:bg-nskgray-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-nskgray-700 mb-2 font-medium" for="reportType">Report Type</label>
                        <select id="reportType" name="report_type"
                            class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors"
                            required>
                            <option value="">Select Report Type</option>
                            <option value="attendance">Attendance Report</option>
                            <option value="academic">Academic Performance</option>
                            <option value="financial">Financial Report</option>
                            <option value="demographic">Student Demographics</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-nskgray-700 mb-2 font-medium" for="reportPeriod">Time Period</label>
                        <select id="reportPeriod" name="report_period"
                            class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors"
                            required>
                            <option value="">Select Time Period</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="term">Term-wise</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-nskgray-700 mb-2 font-medium" for="startDate">Start Date</label>
                            <input type="date" id="startDate" name="start_date"
                                class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors">
                        </div>

                        <div>
                            <label class="block text-nskgray-700 mb-2 font-medium" for="endDate">End Date</label>
                            <input type="date" id="endDate" name="end_date"
                                class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-nskgray-700 mb-2 font-medium" for="reportFormat">Output Format</label>
                        <select id="reportFormat" name="report_format"
                            class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors"
                            required>
                            <option value="">Select Format</option>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button"
                            class="close-modal px-5 py-2.5 border border-nskgray-300 rounded-lg text-nskgray-700 hover:bg-nskgray-50 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="generate_report"
                            class="btn-primary px-5 py-2.5 text-white rounded-lg font-semibold transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="scheduleReportModal" class="modal">
            <div class="modal-content">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Schedule Report</h3>
                    <button
                        class="close-modal text-nskgray-500 hover:text-nskgray-700 p-1 rounded-full hover:bg-nskgray-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-nskgray-700 mb-2 font-medium" for="scheduledReportType">Report
                            Type</label>
                        <select id="scheduledReportType" name="scheduled_report_type"
                            class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors"
                            required>
                            <option value="">Select Report Type</option>
                            <option value="attendance">Attendance Report</option>
                            <option value="academic">Academic Performance</option>
                            <option value="financial">Financial Report</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-nskgray-700 mb-2 font-medium" for="scheduleFrequency">Frequency</label>
                        <select id="scheduleFrequency" name="schedule_frequency"
                            class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors"
                            required>
                            <option value="">Select Frequency</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-nskgray-700 mb-2 font-medium" for="recipientEmail">Recipient
                            Email</label>
                        <input type="email" id="recipientEmail" name="recipient_email"
                            class="w-full px-4 py-2.5 border border-nskgray-300 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue transition-colors"
                            placeholder="Enter email address" required>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button"
                            class="close-modal px-5 py-2.5 border border-nskgray-300 rounded-lg text-nskgray-700 hover:bg-nskgray-50 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="schedule_report"
                            class="btn-primary px-5 py-2.5 text-white rounded-lg font-semibold transition flex items-center">
                            <i class="fas fa-clock mr-2"></i> Schedule Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script src="footer.js"></script>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initializeReportsPage();
        });

        function initializeReportsPage() {
            initializeModals();
            initializeTableActions();
            initializeCharts();
        }

        function initializeModals() {
            const modals = {
                generateReportModal: document.getElementById('generateReportModal'),
                scheduleReportModal: document.getElementById('scheduleReportModal')
            };
            const generateReportBtn = document.getElementById('generateReportBtn');
            const scheduleReportBtn = document.getElementById('scheduleReportBtn');
            const closeModalButtons = document.querySelectorAll('.close-modal');

            function openModal(modalId) {
                const modal = modals[modalId];
                if (modal) {
                    modal.style.display = 'flex';
                    setTimeout(() => modal.classList.add('active'), 10);
                }
            }

            function closeAllModals() {
                Object.values(modals).forEach(modal => {
                    if (modal) {
                        modal.classList.remove('active');
                        setTimeout(() => modal.style.display = 'none', 300);
                    }
                });
            }

            if (generateReportBtn) {
                generateReportBtn.addEventListener('click', () => openModal('generateReportModal'));
            }
            if (scheduleReportBtn) {
                scheduleReportBtn.addEventListener('click', () => openModal('scheduleReportModal'));
            }
            closeModalButtons.forEach(button => {
                button.addEventListener('click', closeAllModals);
            });
            Object.values(modals).forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', (e) => (e.target === modal) && closeAllModals());
                }
            });
            document.addEventListener('keydown', (e) => (e.key === 'Escape') && closeAllModals());
        }

        function initializeTableActions() {
            document.querySelectorAll('.view-report').forEach(button => {
                button.addEventListener('click', function () {
                    const reportId = this.getAttribute('data-report-id');
                    showNotification(`Viewing report ID: ${reportId}`, 'info');
                });
            });

            document.querySelectorAll('.download-report').forEach(button => {
                button.addEventListener('click', function () {
                    const filename = this.getAttribute('data-filename') || 'report.pdf';
                    downloadFile(filename, this);
                });
            });
        }

        function downloadFile(filename, button) {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<div class="loading-spinner"></div>';
            button.disabled = true;

            setTimeout(() => {
                showNotification(`Downloading: ${filename}`, 'success');
                button.innerHTML = originalHTML;
                button.disabled = false;

                const reportContent = `This is a dummy report file for: ${filename}\n\nIn a real application, this file would be a PDF or Excel document generated from the database.`;
                const blob = new Blob([reportContent], {
                    type: 'text/plain'
                });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement('a');
                link.href = url;
                link.download = filename.replace('.pdf', '.txt').replace('.excel', '.txt');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            }, 1500);
        }

        function initializeCharts() {
            const reportTrendsCtx = document.getElementById('reportTrendsChart');
            if (reportTrendsCtx) {
                new Chart(reportTrendsCtx, {
                    type: 'line',
                    data: {
                        labels: <?= $monthlyLabelsJS ?>,
                        datasets: [{
                            label: 'Reports Generated',
                            data: <?= $monthlyCountsJS ?>,
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(30, 64, 175)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                title: {
                                    display: true,
                                    text: 'Number of Reports'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            const reportTypesCtx = document.getElementById('reportTypesChart');
            if (reportTypesCtx) {
                new Chart(reportTypesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= $reportTypeLabelsJS ?>,
                        datasets: [{
                            data: <?= $reportTypeCountsJS ?>,
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(139, 92, 246, 0.8)'
                            ],
                            borderWidth: 0,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        function showNotification(message, type = 'info') {
            const container = document.body;
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-[1001] transform transition-all duration-300 translate-x-full ${type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                    type === 'warning' ? 'bg-yellow-500 text-black' :
                        'bg-blue-500 text-white'
                }`;

            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                        type === 'warning' ? 'fa-exclamation-triangle' :
                            'fa-info-circle'
                } mr-3"></i>
                    <span class="font-medium">${message}</span>
                </div>
            `;

            container.appendChild(notification);

            setTimeout(() => notification.classList.remove('translate-x-full'), 10);

            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
    </script>
</body>

</html>