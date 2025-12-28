<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// session_start();

require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');

// Include the new logger functions
require_once '../config/logger.php';

// Initialize variables
$totalStudents = $totalTeachers = $totalStaff = $totalClasses = 0;
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));
$recentActivities = [];
$studentCountsByClass = [];
$classCapacityData = ['Early Childhood' => 0, 'Primary' => 0, 'Secondary' => 0];

try {
    // Include database configuration
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        // Total Students
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND is_active = 1");
        $stmt->execute();
        $totalStudents = $stmt->fetch()['total'] ?? 0;

        // Total Teachers
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'teacher' AND is_active = 1");
        $stmt->execute();
        $totalTeachers = $stmt->fetch()['total'] ?? 0;

        // Total Classes
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM classes");
        $stmt->execute();
        $totalClasses = $stmt->fetch()['total'] ?? 0;

        // Fetch Recent Activities
        $activityStmt = $db->prepare("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");
        $activityStmt->execute();
        $recentActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch data for charts
        // Students per class (Bar Chart)
        $classStmt = $db->query("SELECT c.class_name, COUNT(s.id) as student_count 
                                 FROM classes c 
                                 LEFT JOIN students s ON c.id = s.class_id 
                                 LEFT JOIN users u ON s.user_id = u.id
                                 WHERE u.is_active = 1 OR s.id IS NULL
                                 GROUP BY c.id 
                                 ORDER BY c.id");
        $classData = $classStmt->fetchAll(PDO::FETCH_ASSOC);
        $classLabels = array_column($classData, 'class_name');
        $classCounts = array_column($classData, 'student_count');

        // Student Distribution (Doughnut Chart)
        $levelStmt = $db->query("SELECT c.class_level, COUNT(s.id) as student_count
                                 FROM classes c 
                                 LEFT JOIN students s ON c.id = s.class_id 
                                 LEFT JOIN users u ON s.user_id = u.id
                                 WHERE (u.is_active = 1 OR s.id IS NULL)
                                 AND c.class_level IN ('Early Childhood', 'Primary', 'Secondary')
                                 GROUP BY c.class_level");
        $levelData = $levelStmt->fetchAll(PDO::FETCH_ASSOC);
        $levelLabels = array_column($levelData, 'class_level');
        $levelCounts = array_column($levelData, 'student_count');

        // Fetch class capacities
        $capacityStmt = $db->query("SELECT id, class_name, class_level, capacity FROM classes");
        $allClassesData = $capacityStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCapacity = 0;
        foreach ($allClassesData as $class) {
            $totalCapacity += $class['capacity'];
            if (isset($classCapacityData[$class['class_level']])) {
                $classCapacityData[$class['class_level']] += $class['capacity'];
            }
        }

        // Fetch Current Term
        $termStmt = $db->prepare("SELECT term_name, start_date, end_date FROM terms WHERE is_current = 1 LIMIT 1");
        $termStmt->execute();
        $currentTerm = $termStmt->fetch(PDO::FETCH_ASSOC);

        if ($currentTerm) {
            $currentTermName = $currentTerm['term_name'];
            $currentTermStart = $currentTerm['start_date'];
            $currentTermEnd = $currentTerm['end_date'];
        } else {
            $currentTermName = 'No Active Term';
            $currentTermStart = '';
            $currentTermEnd = '';
        }
    }
} catch (Exception $e) {
    // Handle error
    $_SESSION['error'] = "Database connection or query failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .nav-item {
            position: relative;
        }

        .nav-item::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #f59e0b;
            transition: width 0.3s ease;
        }

        .nav-item:hover::after {
            width: 100%;
        }

        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
        }

        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body class="flex">
    <?php require_once 'sidebar.php'; ?>


    <main class="main-content">
        <?php
        $pageTitle = 'Admin Dashboard';
        require_once 'header.php';
        ?>

        <div class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nsklightblue p-4 rounded-full mr-4">
                        <i class="fas fa-user-graduate text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= number_format($totalStudents) ?></p>
                    </div>
                </div>

                <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-school text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Classes</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= number_format($totalClasses) ?></p>
                    </div>
                </div>

                <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Teaching Staff</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= number_format($totalTeachers) ?></p>
                    </div>
                </div>

                <div class="dashboard-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-bullhorn text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Notices</p>
                        <p class="text-2xl font-bold text-nsknavy">3</p>
                        <p class="text-xs text-nskred"><i class="fas fa-exclamation-circle"></i> New alerts</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Student Distribution by Section</h2>
                    <div class="chart-container">
                        <canvas id="sectionDistributionChart"></canvas>
                    </div>
                </div>

                <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Students per Class</h2>
                    <div class="chart-container">
                        <canvas id="classDistributionChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-xl shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold text-nsknavy mb-6">Detailed Class Breakdown</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-nskblue mb-4 flex items-center">
                            <i class="fas fa-baby mr-2"></i> Early Childhood
                        </h3>
                        <div class="space-y-3">
                            <?php
                            $levelClassCount = 0;
                            foreach ($allClassesData as $class):
                                if ($class['class_level'] == 'Early Childhood'):
                                    $levelClassCount++;
                            ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700"><?= htmlspecialchars($class['class_name']) ?></span>
                                        <span class="font-bold text-nskblue"><?= $class['capacity'] ?></span>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between items-center font-semibold">
                                    <span>Total Classes</span>
                                    <span class="text-nskblue"><?= $levelClassCount ?></span>
                                </div>
                                <div class="flex justify-between items-center font-semibold">
                                    <span>Total Capacity</span>
                                    <span class="text-nskblue"><?= $classCapacityData['Early Childhood'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-nskgreen mb-4 flex items-center">
                            <i class="fas fa-child mr-2"></i> Primary School
                        </h3>
                        <div class="space-y-3">
                            <?php
                            $levelClassCount = 0;
                            foreach ($allClassesData as $class):
                                if ($class['class_level'] == 'Primary'):
                                    $levelClassCount++;
                            ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700"><?= htmlspecialchars($class['class_name']) ?></span>
                                        <span class="font-bold text-nskgreen"><?= $class['capacity'] ?></span>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between items-center font-semibold">
                                    <span>Total Classes</span>
                                    <span class="text-nskgreen"><?= $levelClassCount ?></span>
                                </div>
                                <div class="flex justify-between items-center font-semibold">
                                    <span>Total Capacity</span>
                                    <span class="text-nskgreen"><?= $classCapacityData['Primary'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-nskgold mb-4 flex items-center">
                            <i class="fas fa-user-graduate mr-2"></i> Secondary School
                        </h3>
                        <div class="space-y-3">
                            <?php
                            $levelClassCount = 0;
                            foreach ($allClassesData as $class):
                                if ($class['class_level'] == 'Secondary'):
                                    $levelClassCount++;
                            ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700"><?= htmlspecialchars($class['class_name']) ?></span>
                                        <span class="font-bold text-nskgold"><?= $class['capacity'] ?></span>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between items-center font-semibold">
                                    <span>Total Classes</span>
                                    <span class="text-nskgold"><?= $levelClassCount ?></span>
                                </div>
                                <div class="flex justify-between items-center font-semibold">
                                    <span>Total Capacity</span>
                                    <span class="text-nskgold"><?= $classCapacityData['Secondary'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-nsklight rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-600">Total Classes</p>
                            <p class="text-2xl font-bold text-nsknavy"><?= $totalClasses ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Capacity</p>
                            <p class="text-2xl font-bold text-nskgreen"><?= $totalCapacity ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Current Enrollment</p>
                            <p class="text-2xl font-bold text-nskblue"><?= number_format($totalStudents) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Available Spaces</p>
                            <p class="text-2xl font-bold text-nskgold"><?= $totalCapacity - $totalStudents ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="current-term bg-white shadow-md rounded-lg p-4 mb-6">
                <h2 class="text-xl font-bold text-nskblue">Current Term</h2>
                <p class="text-gray-700">Term: <span class="font-semibold"><?= $currentTermName ?></span></p>
                <p class="text-gray-700">Start Date: <span class="font-semibold"><?= $currentTermStart ?></span></p>
                <p class="text-gray-700">End Date: <span class="font-semibold"><?= $currentTermEnd ?></span></p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Recent Activities</h2>
                    <div class="space-y-4">
                        <?php if (empty($recentActivities)): ?>
                            <p class="text-gray-500">No recent activities found.</p>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="flex items-start">
                                    <div class="<?= htmlspecialchars($activity['color']) ?> p-2 rounded-full mr-3 mt-1">
                                        <i class="<?= htmlspecialchars($activity['icon']) ?> text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><?= htmlspecialchars($activity['action_type']) ?></p>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($activity['description']) ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?= htmlspecialchars(time_elapsed_string($activity['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Upcoming Events</h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-nskblue text-white text-center p-2 rounded mr-3" style="min-width: 45px;">
                                <p class="text-sm font-bold">20</p>
                                <p class="text-xs">NOV</p>
                            </div>
                            <div>
                                <p class="font-semibold">Annual Sports Day</p>
                                <p class="text-sm text-gray-600">School grounds, 9:00 AM</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-nskgreen text-white text-center p-2 rounded mr-3" style="min-width: 45px;">
                                <p class="text-sm font-bold">25</p>
                                <p class="text-xs">NOV</p>
                            </div>
                            <div>
                                <p class="font-semibold">Parent-Teacher Meeting</p>
                                <p class="text-sm text-gray-600">All classrooms, 2:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="./footer.js"></script>
    </main>

    <script>
        // Wait for everything to load including sidebar
        window.addEventListener('load', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.toggle('mobile-show');
                    }
                });
            }

            // Initialize charts after a small delay to ensure canvas is ready
            setTimeout(function() {
                initializeStudentCharts();
            }, 200);
        });

        function initializeStudentCharts() {
            // Section Distribution Chart (Doughnut Chart)
            const sectionCanvas = document.getElementById('sectionDistributionChart');
            if (sectionCanvas) {
                const sectionCtx = sectionCanvas.getContext('2d');
                new Chart(sectionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($levelLabels) ?>,
                        datasets: [{
                            data: <?= json_encode($levelCounts) ?>,
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)'
                            ],
                            borderColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} students (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }

            // Class Distribution Chart (Bar Chart)
            const classCanvas = document.getElementById('classDistributionChart');
            if (classCanvas) {
                const classCtx = classCanvas.getContext('2d');
                new Chart(classCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($classLabels) ?>,
                        datasets: [{
                            label: 'Number of Students',
                            data: <?= json_encode($classCounts) ?>,
                            backgroundColor: [
                                // Early Childhood - Blue
                                'rgba(59, 130, 246, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(59, 130, 246, 0.7)',
                                // Primary - Green
                                'rgba(16, 185, 129, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(16, 185, 129, 0.7)',
                                // Secondary - Gold
                                'rgba(245, 158, 11, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(245, 158, 11, 0.7)'
                            ],
                            borderColor: [
                                'rgb(59, 130, 246)', 'rgb(59, 130, 246)', 'rgb(59, 130, 246)', 'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)', 'rgb(16, 185, 129)', 'rgb(16, 185, 129)', 'rgb(16, 185, 129)', 'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)', 'rgb(245, 158, 11)', 'rgb(245, 158, 11)', 'rgb(245, 158, 11)', 'rgb(245, 158, 11)', 'rgb(245, 158, 11)'
                            ],
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(30, 64, 175, 0.9)',
                                padding: 12,
                                titleFont: {
                                    size: 14
                                },
                                bodyFont: {
                                    size: 13
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Students'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
            }
        }

        // Simulate live notifications
        setInterval(() => {
            const notifications = document.querySelectorAll('.notification-dot');
            notifications.forEach(dot => {
                dot.style.display = Math.random() > 0.3 ? 'block' : 'none';
            });
        }, 3000);
    </script>
</body>

</html>