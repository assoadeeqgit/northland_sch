<?php
require_once 'auth-check.php';
checkAuth(); // Ensure user is authenticated
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        nskred: '#ef4444'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }

        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        .timetable-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .timetable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
        }

        .timetable-cell {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .timetable-cell:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .subject-music {
            background-color: #f0abfc;
            border-left: 4px solid #c026d3;
        }

        .subject-break {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .subject-sensory {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
        }

        .subject-story {
            background-color: #e0e7ff;
            border-left: 4px solid #4f46e5;
        }

        .subject-math {
            background-color: #bfdbfe;
            border-left: 4px solid #3b82f6;
        }

        .subject-science {
            background-color: #bbf7d0;
            border-left: 4px solid #10b981;
        }

        .subject-english {
            background-color: #fde68a;
            border-left: 4px solid #f59e0b;
        }

        .subject-history {
            background-color: #e9d5ff;
            border-left: 4px solid #8b5cf6;
        }

        .subject-art {
            background-color: #fecaca;
            border-left: 4px solid #ef4444;
        }

        .subject-pe {
            background-color: #c7d2fe;
            border-left: 4px solid #6366f1;
        }

        .subject-religious {
            background-color: #ddd6fe;
            border-left: 4px solid #7c3aed;
        }

        .subject-computer {
            background-color: #a7f3d0;
            border-left: 4px solid #059669;
        }

        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: scale(0.9);
            opacity: 0;
            pointer-events: none;
        }

        .modal.active {
            transform: scale(1);
            opacity: 1;
            pointer-events: all;
        }

        .tab-button {
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background-color: #1e40af;
            color: white;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        .hidden {
            display: none;
        }

        .template-card {
            transition: all 0.3s ease;
        }

        .template-card:hover {
            border-color: #1e40af;
            transform: translateY(-2px);
        }

        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
        }

        .export-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
    </style>
</head>

<body class="flex">
    <!-- Sidebar Navigation -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php
        $pageTitle = 'Timetable Management';
        require_once 'header.php';
        ?>

        <!-- Timetable Management Content -->
        <div class="p-6">
            <!-- Stats Overview -->
            <?php
            // Fetch dynamic stats
            $activeSchedulesCount = 0;
            $periodsPerDay = 8; // Default
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = new Database();
                $conn = $db->getConnection();

                // Count classes that have at least one timetable entry
                $activeSchedulesCount = $conn->query("SELECT COUNT(DISTINCT class_id) FROM timetable")->fetchColumn();

                // Get max period number
                $maxPeriod = $conn->query("SELECT MAX(period_id) FROM timetable")->fetchColumn();
                if ($maxPeriod) {
                    $periodsPerDay = $maxPeriod;
                }
            } catch (Exception $e) {
                // Keep defaults
            }
            ?>
            <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                <div class="bg-nsklightblue p-4 rounded-full mr-4">
                    <i class="fas fa-calendar-day text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">Active Schedules</p>
                    <p class="text-2xl font-bold text-nsknavy"><?= $activeSchedulesCount ?></p>
                    <p class="text-xs text-nskgreen">Classes with timetables</p>
                </div>
            </div>

            <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                <div class="bg-nskgreen p-4 rounded-full mr-4">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">Periods per Day</p>
                    <p class="text-2xl font-bold text-nsknavy"><?= $periodsPerDay ?></p>
                    <p class="text-xs text-gray-600">Based on schedule</p>
                </div>
            </div>

            <?php
            // Dynamic classrooms stats: count distinct rooms and rooms occupied right now
            $classroomCount = 0;
            $occupiedNow = 0;
            $occupiedText = 'No data';
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
                if ($conn) {
                    // determine current academic session and term if available
                    $sessionId = null;
                    $termId = null;
                    $r = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1")->fetch();
                    if ($r)
                        $sessionId = (int) $r['id'];
                    $r2 = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1")->fetch();
                    if ($r2)
                        $termId = (int) $r2['id'];

                    $params = [];
                    $whereSess = '';
                    if ($sessionId) {
                        $whereSess .= ' AND academic_session_id = :sess';
                        $params[':sess'] = $sessionId;
                    }
                    if ($termId) {
                        $whereSess .= ' AND term_id = :term';
                        $params[':term'] = $termId;
                    }

                    $sqlTotal = "SELECT COUNT(DISTINCT room) AS total FROM timetable WHERE room IS NOT NULL" . $whereSess;
                    $stmt = $conn->prepare($sqlTotal);
                    $stmt->execute($params);
                    $tot = $stmt->fetch();
                    $classroomCount = (int) ($tot['total'] ?? 0);

                    // occupied now: rooms with an entry for current day and current time
                    $day = date('l');
                    $now = date('H:i:s');
                    $sqlOcc = "SELECT COUNT(DISTINCT room) AS occ FROM timetable WHERE day_of_week = :day AND start_time <= :now AND end_time > :now" . $whereSess;
                    $stmt2 = $conn->prepare($sqlOcc);
                    $stmt2->bindValue(':day', $day);
                    $stmt2->bindValue(':now', $now);
                    foreach ($params as $k => $v)
                        $stmt2->bindValue($k, $v);
                    $stmt2->execute();
                    $occ = $stmt2->fetch();
                    $occupiedNow = (int) ($occ['occ'] ?? 0);

                    if ($classroomCount > 0) {
                        $occupiedText = ($occupiedNow >= $classroomCount) ? 'All occupied' : "$occupiedNow occupied";
                    } else {
                        $occupiedText = 'No rooms found';
                    }
                }
            } catch (Exception $e) {
                // keep defaults on error
            }
            ?>
            <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                <div class="bg-nskgold p-4 rounded-full mr-4">
                    <i class="fas fa-chalkboard text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">Classrooms</p>
                    <p class="text-2xl font-bold text-nsknavy"><?php echo htmlspecialchars($classroomCount); ?></p>
                    <p class="text-xs text-nskgreen"><?php echo htmlspecialchars($occupiedText); ?></p>
                </div>
            </div>

            <!-- <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Conflicts</p>
                        <p class="text-2xl font-bold text-nsknavy">3</p>
                        <p class="text-xs text-nskred">Needs resolution</p>
                    </div>
                </div> -->
        </div>

        <!-- Class Level Selection -->
        <div class="filter-section p-6 mb-8">
            <div class="mb-6">
                <div class="flex flex-wrap gap-2 mb-4">
                    <button class="level-btn px-4 py-2 rounded-lg border border-nskblue text-nskblue"
                        data-level="early-childhood">
                        Early Childhood
                    </button>
                    <button class="level-btn active px-4 py-2 rounded-lg bg-nskblue text-white" data-level="primary">
                        Primary School
                    </button>
                    <button class="level-btn px-4 py-2 rounded-lg border border-nskblue text-nskblue"
                        data-level="secondary">
                        Secondary School
                    </button>
                </div>

                <!-- Class selections (populated from DB) -->
                <?php
                require_once __DIR__ . '/../config/database.php';
                $db = new Database();
                $conn = $db->getConnection();

                // Fetch School Name
                $schoolName = 'Northland Schools Kano'; // Default
                // The following block was removed as per instruction:
                // if ($conn) {
                //     $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'school_name'");
                //     $val = $stmt->fetchColumn();
                //     if ($val)
                //         $schoolName = $val;
                // }
                
                $classesByLevel = ['Early Childhood' => [], 'Primary' => [], 'Secondary' => []];
                if ($conn) {
                    $stmt = $conn->query("SELECT id, class_name, class_level FROM classes ORDER BY class_level, class_name");
                    while ($r = $stmt->fetch()) {
                        $level = $r['class_level'] ?: 'Primary';
                        if (!isset($classesByLevel[$level]))
                            $classesByLevel[$level] = [];
                        $classesByLevel[$level][] = $r;
                    }
                }
                ?>


                <div id="earlyChildhoodClasses" class="class-selection hidden">
                    <select id="classSelectEarly" class="class-selector px-4 py-2 border rounded-lg w-full md:w-64">
                        <option value="">Select Early Childhood Class</option>
                        <?php foreach ($classesByLevel['Early Childhood'] as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['id']); ?>">
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="primaryClasses" class="class-selection">
                    <select id="classSelectPrimary" class="class-selector px-4 py-2 border rounded-lg w-full md:w-64">
                        <option value="">Select Primary Class</option>
                        <?php foreach ($classesByLevel['Primary'] as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['id']); ?>">
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="secondaryClasses" class="class-selection hidden">
                    <select id="classSelectSecondary" class="class-selector px-4 py-2 border rounded-lg w-full md:w-64">
                        <option value="">Select Secondary Class</option>
                        <?php foreach ($classesByLevel['Secondary'] as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['id']); ?>">
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- View Options -->
            <div class="view-options flex flex-wrap gap-2 mb-4">
                <button class="view-btn active px-4 py-2 rounded-lg bg-nskblue text-white" data-view="weekly">
                    Weekly View
                </button>
                <button class="view-btn px-4 py-2 rounded-lg border border-nskblue text-nskblue" data-view="daily">
                    Daily View
                </button>
                <!-- <button class="view-btn px-4 py-2 rounded-lg border border-nskblue text-nskblue" data-view="teacher">
                        Teacher View
                    </button> -->
            </div>

            <!-- Export Options -->
            <div class="export-options flex flex-wrap gap-2 mb-4">
                <button id="exportPdfBtn" class="export-btn px-4 py-2 rounded-lg text-white">
                    <i class="fas fa-download mr-2"></i>Export PDF
                </button>
                <button id="printBtn"
                    class="px-4 py-2 rounded-lg border border-nskblue text-nskblue hover:bg-nskblue hover:text-white transition">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <button id="exportExcelBtn"
                    class="px-4 py-2 rounded-lg border border-nskgold text-nskgold hover:bg-nskgold hover:text-white transition">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </button>
                <a href="download_timetable_template.php" target="_blank"
                    class="px-4 py-2 rounded-lg border border-gray-500 text-gray-500 hover:bg-gray-500 hover:text-white transition">
                    <i class="fas fa-download mr-2"></i>Template
                </a>
            </div>
        </div>

        <!-- Conflict Alert -->
        <div id="conflictAlert" class="hidden bg-nskred bg-opacity-10 border border-nskred rounded-lg p-4 mb-8">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-nskred mr-3"></i>
                <div>
                    <p class="font-semibold text-nskred">Schedule Conflicts Detected</p>
                    <p class="text-sm" id="conflictDetails">Teacher conflict: Mr. Johnson scheduled in two classes
                        at the same time.</p>
                </div>
                <button class="ml-auto bg-nskred text-white px-3 py-1 rounded text-sm">
                    Resolve Conflicts
                </button>
            </div>
        </div>

        <!-- Bulk Operations -->
        <div class="bulk-operations bg-white rounded-xl shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-nsknavy mb-4">Bulk Operations</h3>
            <div class="flex flex-wrap gap-4">
                <button class="bulk-btn px-4 py-2 rounded-lg border border-nskblue text-nskblue">
                    <i class="fas fa-copy mr-2"></i>Copy to Other Classes
                </button>
                <button class="bulk-btn px-4 py-2 rounded-lg border border-nskgreen text-nskgreen">
                    <i class="fas fa-sync-alt mr-2"></i>Apply Template
                </button>
                <button class="bulk-btn px-4 py-2 rounded-lg border border-nskred text-nskred">
                    <i class="fas fa-trash-alt mr-2"></i>Clear Schedule
                </button>
            </div>
        </div>

        <!-- Template Management -->
        <div class="template-management bg-white rounded-xl shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-nsknavy mb-4">Timetable Templates</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="template-card border rounded-lg p-4 cursor-pointer hover:border-nskblue">
                    <h4 class="font-semibold">Primary School Template</h4>
                    <p class="text-sm text-gray-600">8 periods, 45 mins each</p>
                </div>
                <div class="template-card border rounded-lg p-4 cursor-pointer hover:border-nskblue">
                    <h4 class="font-semibold">Secondary School Template</h4>
                    <p class="text-sm text-gray-600">8 periods, 45 mins each</p>
                </div>
                <div class="template-card border rounded-lg p-4 cursor-pointer hover:border-nskblue">
                    <h4 class="font-semibold">Custom Template</h4>
                    <p class="text-sm text-gray-600">Create your own</p>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h2 class="text-xl font-bold text-nsknavy">Class Timetables</h2>

                <div class="flex flex-wrap gap-4">
                    <button id="generateTimetableBtn"
                        class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                        <i class="fas fa-magic mr-2"></i> Auto-Generate
                    </button>

                    <button id="importTimetableBtn"
                        class="bg-nsknavy text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-800 transition flex items-center">
                        <i class="fas fa-file-import mr-2"></i> Import
                    </button>
                    <input type="file" id="importCsvInput" class="hidden" accept=".csv">

                    <button id="addScheduleBtn"
                        class="bg-nskgold text-white px-4 py-2 rounded-lg font-semibold hover:bg-amber-600 transition flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Schedule
                    </button>
                </div>
            </div>
        </div>

        <!-- Timetable Display Area -->
        <div id="timetableDisplay" class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                <p>Select a class to view the timetable</p>
            </div>
        </div>

        <!-- Upcoming Exams -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold text-nsknavy mb-6">Upcoming Examinations</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border-l-4 border-nskblue pl-4 py-2">
                    <p class="font-semibold">Mid-Term Examinations</p>
                    <p class="text-sm text-gray-600">15th - 19th November 2023</p>
                    <p class="text-xs text-nskblue">2 weeks remaining</p>
                </div>

                <div class="border-l-4 border-nskgreen pl-4 py-2">
                    <p class="font-semibold">Mathematics Quiz</p>
                    <p class="text-sm text-gray-600">22nd November 2023</p>
                    <p class="text-xs text-nskgreen">Grade 10 Only</p>
                </div>

                <div class="border-l-4 border-nskgold pl-4 py-2">
                    <p class="font-semibold">Science Practical</p>
                    <p class="text-sm text-gray-600">25th November 2023</p>
                    <p class="text-xs text-nskgold">Physics & Chemistry</p>
                </div>
            </div>

            <div class="mt-6">
                <button
                    class="bg-nsklightblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nskblue transition flex items-center">
                    <i class="fas fa-plus mr-2"></i> Schedule New Exam
                </button>
            </div>
        </div>
        </div>

        <!-- Add Schedule Modal -->
        <div id="addScheduleModal"
            class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Add New Schedule</h3>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="scheduleForm" class="space-y-4">
                    <!-- Class Level Selection -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2" for="level">School Level</label>
                            <select id="level"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Level</option>
                                <option value="primary">Primary School</option>
                                <option value="secondary">Secondary School</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="class">Class</label>
                            <select id="class"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Class</option>
                                <!-- Options will be populated dynamically based on level -->
                            </select>
                        </div>
                    </div>

                    <!-- Subject and Teacher -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2" for="subject">Subject</label>
                            <select id="subject"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Subject</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="teacher">Teacher</label>
                            <select id="teacher"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Teacher</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <!-- Schedule Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2" for="day">Day</label>
                            <select id="day" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                                required>
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="period">Period</label>
                            <select id="period"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Period</option>
                                <option value="1" data-start="08:00" data-end="08:40">Period 1 (08:00 - 08:40)</option>
                                <option value="2" data-start="08:40" data-end="09:20">Period 2 (08:40 - 09:20)</option>
                                <option value="3" data-start="09:20" data-end="10:00">Period 3 (09:20 - 10:00)</option>
                                <option value="4" data-start="10:00" data-end="10:40">Period 4 (10:00 - 10:40)</option>
                                <option value="break" data-start="10:40" data-end="11:00">Break (10:40 - 11:00)</option>
                                <option value="5" data-start="11:00" data-end="11:40">Period 5 (11:00 - 11:40)</option>
                                <option value="6" data-start="11:40" data-end="12:20">Period 6 (11:40 - 12:20)</option>
                                <option value="7" data-start="12:20" data-end="13:00">Period 7 (12:20 - 13:00)</option>
                                <option value="8" data-start="13:00" data-end="13:40">Period 8 (13:00 - 13:40)</option>
                            </select>
                            <input type="hidden" id="startTime" name="startTime">
                            <input type="hidden" id="endTime" name="endTime">
                        </div>
                    </div>

                    <!-- Additional Options -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2" for="classroom">Classroom</label>
                            <select id="classroom"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Classroom</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="recurring">Recurring</label>
                            <select id="recurring"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" id="cancelBtn"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                            Add Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </div>

        <!-- Floating Action Button -->
        <button
            class="floating-action-btn w-14 h-14 bg-nskblue text-white rounded-full flex items-center justify-center shadow-lg hover:bg-nsknavy transition">
            <i class="fas fa-plus text-xl"></i>
        </button>

        <!-- Include footer -->
        <script src="footer.js"></script>
    </main>

    <script>
        const SCHOOL_NAME = "<?php echo isset($schoolName) ? addslashes($schoolName) : 'Northland Schools Kano'; ?>";
        // Enhanced Timetable Manager
        class TimetableManager {
            constructor() {
                this.currentLevel = 'primary';
                this.currentClass = '';
                this.currentView = 'weekly';

                this.periodsSecondary = [
                    { name: 'Period 1', start: '08:00', end: '08:40' },
                    { name: 'Period 2', start: '08:40', end: '09:20' },
                    { name: 'Period 3', start: '09:20', end: '10:00' },
                    { name: 'Period 4', start: '10:00', end: '10:40' },
                    { name: 'Break', start: '10:40', end: '11:00' },
                    { name: 'Period 5', start: '11:00', end: '11:40' },
                    { name: 'Period 6', start: '11:40', end: '12:20' },
                    { name: 'Period 7', start: '12:20', end: '13:00' },
                    { name: 'Period 8', start: '13:00', end: '13:40' }
                ];

                this.periodsPrimary = [
                    { name: 'Period 1', start: '08:00', end: '08:30' },
                    { name: 'Period 2', start: '08:30', end: '09:00' },
                    { name: 'Period 3', start: '09:00', end: '09:30' },
                    { name: 'Period 4', start: '09:30', end: '10:00' },
                    { name: 'Break', start: '10:00', end: '10:30' },
                    { name: 'Period 5', start: '10:30', end: '11:00' },
                    { name: 'Period 6', start: '11:00', end: '11:30' },
                    { name: 'Period 7', start: '11:30', end: '12:00' },
                    { name: 'Period 8', start: '12:00', end: '12:30' },
                    { name: 'Period 9', start: '12:30', end: '13:00' },
                    { name: 'Period 10', start: '13:00', end: '13:30' }
                ];

                this.periodsEarlyYears = [
                    { name: 'Period 1', start: '08:00', end: '08:30' },
                    { name: 'Period 2', start: '08:30', end: '09:00' },
                    { name: 'Period 3', start: '09:00', end: '09:30' },
                    { name: 'Break', start: '09:30', end: '10:00' },
                    { name: 'Period 4', start: '10:00', end: '10:30' },
                    { name: 'Period 5', start: '10:30', end: '11:00' },
                    { name: 'Period 6', start: '11:00', end: '11:30' },
                    { name: 'Period 7', start: '11:30', end: '12:00' },
                    { name: 'Period 8', start: '12:00', end: '12:30' },
                    { name: 'Period 9', start: '12:30', end: '13:00' },
                    { name: 'Period 10', start: '13:00', end: '13:30' }
                ];

                this.init();
            }

            init() {
                this.setupEventListeners();
                this.loadInitialData();
                this.populateModalOptions();
            }

            setupEventListeners() {
                // Level switching
                document.querySelectorAll('.level-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        this.switchLevel(e.currentTarget.dataset.level, e.currentTarget);
                    });
                });

                // Class selection
                document.querySelectorAll('.class-selector').forEach(select => {
                    select.addEventListener('change', (e) => {
                        this.currentClass = e.target.value;
                        this.loadTimetable(e.target.value);
                    });
                });

                // View switching
                document.querySelectorAll('.view-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        this.switchView(e.currentTarget.dataset.view, e.currentTarget);
                    });
                });

                // Modal level change
                document.getElementById('level').addEventListener('change', (e) => {
                    this.updateModalClasses(e.target.value);
                    this.updateModalSubjects(e.target.value);
                });

                // Period selection to set start/end time
                document.getElementById('period').addEventListener('change', (e) => {
                    const option = e.target.options[e.target.selectedIndex];
                    if (option.value) {
                        document.getElementById('startTime').value = option.dataset.start;
                        document.getElementById('endTime').value = option.dataset.end;
                    }
                });
            }

            switchLevel(level) {
                this.currentLevel = level;

                // Update UI
                document.querySelectorAll('.level-btn').forEach(btn => {
                    btn.classList.remove('active', 'bg-nskblue', 'text-white');
                    btn.classList.add('border', 'border-nskblue', 'text-nskblue');
                });

                // If caller provided the button element, mark it active
                const activeBtn = arguments[1] || document.querySelector(`.level-btn[data-level="${level}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active', 'bg-nskblue', 'text-white');
                    activeBtn.classList.remove('border', 'border-nskblue', 'text-nskblue');
                }

                // Show/hide class selections
                document.getElementById('earlyChildhoodClasses').classList.toggle('hidden', level !== 'early-childhood');
                document.getElementById('primaryClasses').classList.toggle('hidden', level !== 'primary');
                document.getElementById('secondaryClasses').classList.toggle('hidden', level !== 'secondary');

                // Reset class selection
                this.currentClass = '';
                this.showTimetablePlaceholder();

                // Update modal periods
                this.updateModalPeriods(level);
            }

            switchView(view) {
                this.currentView = view;

                // Update UI
                document.querySelectorAll('.view-btn').forEach(btn => {
                    btn.classList.remove('active', 'bg-nskblue', 'text-white');
                    btn.classList.add('border', 'border-nskblue', 'text-nskblue');
                });

                const activeViewBtn = arguments[1] || document.querySelector(`.view-btn[data-view="${view}"]`);
                if (activeViewBtn) {
                    activeViewBtn.classList.add('active', 'bg-nskblue', 'text-white');
                    activeViewBtn.classList.remove('border', 'border-nskblue', 'text-nskblue');
                }

                // Reload timetable with new view
                if (this.currentClass) {
                    this.loadTimetable(this.currentClass);
                }
            }

            showTimetablePlaceholder() {
                document.getElementById('timetableDisplay').innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p>Select a class to view the timetable</p>
                    </div>
                `;
            }

            showLoadingState() {
                document.getElementById('timetableDisplay').innerHTML = `
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-nskblue"></div>
                        <p class="mt-4 text-gray-600">Loading timetable...</p>
                    </div>
                `;
            }

            loadTimetable(className) {
                this.currentClass = className;

                if (!className) {
                    this.showTimetablePlaceholder();
                    return;
                }

                // Show loading
                this.showLoadingState();

                // Simulate API call
                setTimeout(() => {
                    let data;
                    if (this.currentLevel === 'early-childhood') {
                        data = this.getEarlyChildhoodTimetable();
                    } else if (this.currentLevel === 'primary') {
                        data = this.getPrimaryTimetable();
                    } else {
                        data = this.getSecondaryTimetable();
                    }
                    this.displayTimetable(className, data);
                }, 500);
            }

            subjectToCss(name) {
                if (!name) return '';
                const n = name.toLowerCase();
                if (n.includes('break')) return 'subject-break';
                if (n.includes('math') || n.includes('mathemat')) return 'subject-math';
                if (n.includes('science') || n.includes('phy') || n.includes('chem') || n.includes('bio')) return 'subject-science';
                if (n.includes('english') || n.includes('literature')) return 'subject-english';
                if (n.includes('history') || n.includes('social')) return 'subject-history';
                if (n.includes('music')) return 'subject-music';
                if (n.includes('art')) return 'subject-art';
                if (n.includes('sport') || n.includes('physical') || n.includes('pe')) return 'subject-pe';
                if (n.includes('relig') || n.includes('irs') || n.includes('crs')) return 'subject-religious';
                return 'template-card';
            }

            displayTimetable(className, timetableData) {
                const displayArea = document.getElementById('timetableDisplay');

                // Generate timetable based on current view
                if (this.currentView === 'weekly') {
                    displayArea.innerHTML = this.generateWeeklyTimetable(className, timetableData);
                } else if (this.currentView === 'daily') {
                    displayArea.innerHTML = this.generateDailyTimetable(className, timetableData);
                } else {
                    displayArea.innerHTML = this.generateTeacherTimetable(className, timetableData);
                }

                // Add click events to timetable cells
                this.addTimetableCellEvents();
            }

            generateWeeklyTimetable(className, data) {
                const isEarlyChildhood = this.currentLevel === 'early-childhood';
                const activityKey = isEarlyChildhood ? 'activity' : 'subject';

                return `
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Weekly Timetable for ${className.toUpperCase()}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="bg-nsklightblue text-white p-3">Time/Day</th>
                                    <th class="bg-nsklightblue text-white p-3">Monday</th>
                                    <th class="bg-nsklightblue text-white p-3">Tuesday</th>
                                    <th class="bg-nsklightblue text-white p-3">Wednesday</th>
                                    <th class="bg-nsklightblue text-white p-3">Thursday</th>
                                    <th class="bg-nsklightblue text-white p-3">Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(row => `
                                    <tr>
                                        <td class="bg-nsklight p-3 font-semibold text-center whitespace-nowrap">${row.time}</td>
                                        ${row.days.map(day => `
                                            <td class="p-2">
                                                ${day ? `
                                                    <div class="timetable-cell ${day.class} p-3 rounded-lg">
                                                        <p class="font-semibold">${day[activityKey]}</p>
                                                        ${!day[activityKey].includes('Break') ? `
                                                            <p class="text-sm">${day.teacher}</p>
                                                            <p class="text-xs">${day.room}</p>
                                                        ` : ''}
                                                    </div>
                                                ` : `
                                                    <div class="bg-gray-100 p-3 rounded-lg text-center text-gray-500">
                                                        <p class="font-semibold">FREE</p>
                                                    </div>
                                                `}
                                            </td>
                                        `).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            generateDailyTimetable(className, data) {
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                const selectedValue = (document.getElementById('dailyDaySelector')?.value || 'monday').toString().toLowerCase();
                const dayIndex = days.map(d => d.toLowerCase()).indexOf(selectedValue);
                const selectedDay = dayIndex >= 0 ? dayIndex : 0;

                const isEarlyChildhood = this.currentLevel === 'early-childhood';
                const activityKey = isEarlyChildhood ? 'activity' : 'subject';

                return `
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-nsknavy">Daily Timetable for ${className.toUpperCase()} - ${days[selectedDay]}</h3>
                        <div class="flex items-center space-x-2">
                            <select id="dailyDaySelector" class="px-3 py-1 border rounded-lg" onchange="timetableManager.displayTimetable('${className}', ${JSON.stringify(data).replace(/"/g, '&quot;')})">
                                ${days.map((day, index) => `
                                    <option value="${day.toLowerCase()}" ${index === selectedDay ? 'selected' : ''}>${day}</option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="bg-nsklightblue text-white p-3">Time</th>
                                    <th class="bg-nsklightblue text-white p-3">${activityKey.charAt(0).toUpperCase() + activityKey.slice(1)}</th>
                                    <th class="bg-nsklightblue text-white p-3">Teacher</th>
                                    <th class="bg-nsklightblue text-white p-3">Room</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(row => {
                    const day = row.days[selectedDay];
                    return `
                                        <tr>
                                            <td class="bg-nsklight p-3 font-semibold text-center whitespace-nowrap">${row.time}</td>
                                            <td class="p-3">
                                                ${day ? `
                                                    <div class="timetable-cell ${day.class} p-3 rounded-lg">
                                                        <p class="font-semibold">${day[activityKey]}</p>
                                                    </div>
                                                ` : `
                                                    <div class="bg-gray-100 p-3 rounded-lg text-center text-gray-500">
                                                        <p class="font-semibold">FREE</p>
                                                    </div>
                                                `}
                                            </td>
                                            <td class="p-3 text-center">${day ? (day[activityKey].includes('Break') ? '-' : day.teacher) : '-'}</td>
                                            <td class="p-3 text-center">${day ? (day[activityKey].includes('Break') ? '-' : day.room) : '-'}</td>
                                        </tr>
                                    `;
                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            generateTeacherTimetable(className, data) {
                // Simplified teacher view - in a real app, this would show teacher schedules
                return `
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Teacher Schedule View for ${className.toUpperCase()}</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-yellow-500 mr-3"></i>
                            <p>Teacher view shows all classes assigned to teachers. This feature is under development.</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-600">In the full version, this view would display:</p>
                        <ul class="list-disc list-inside mt-2 text-gray-600">
                            <li>Teacher schedules across all classes</li>
                            <li>Teacher availability and free periods</li>
                            <li>Teacher workload distribution</li>
                        </ul>
                    </div>
                `;
            }

            getEarlyChildhoodTimetable() {
                return [{
                    time: '8:00 - 8:30',
                    days: [{
                        activity: 'Arrival & Free Play',
                        teacher: 'Teacher Sarah',
                        room: 'Play Area',
                        class: 'subject-art'
                    },
                    {
                        activity: 'Morning Circle',
                        teacher: 'Teacher Sarah',
                        room: 'Carpet Area',
                        class: 'subject-english'
                    },
                    {
                        activity: 'Arrival & Free Play',
                        teacher: 'Teacher Sarah',
                        room: 'Play Area',
                        class: 'subject-art'
                    },
                    {
                        activity: 'Morning Circle',
                        teacher: 'Teacher Sarah',
                        room: 'Carpet Area',
                        class: 'subject-english'
                    },
                    {
                        activity: 'Arrival & Free Play',
                        teacher: 'Teacher Sarah',
                        room: 'Play Area',
                        class: 'subject-art'
                    }
                    ]
                },
                {
                    time: '8:30 - 9:00',
                    days: [{
                        activity: 'Arts & Crafts',
                        teacher: 'Teacher Sarah',
                        room: 'Art Corner',
                        class: 'subject-art'
                    },
                    {
                        activity: 'Story Time',
                        teacher: 'Teacher Sarah',
                        room: 'Reading Corner',
                        class: 'subject-story'
                    },
                    {
                        activity: 'Music & Movement',
                        teacher: 'Teacher Sarah',
                        room: 'Activity Area',
                        class: 'subject-music'
                    },
                    {
                        activity: 'Puzzle Time',
                        teacher: 'Teacher Sarah',
                        room: 'Learning Center',
                        class: 'subject-math'
                    },
                    {
                        activity: 'Outdoor Play',
                        teacher: 'Teacher Sarah',
                        room: 'Playground',
                        class: 'subject-pe'
                    }
                    ]
                },
                {
                    time: '9:00 - 9:30',
                    days: [{
                        activity: 'Snack Time',
                        teacher: 'Teacher Sarah',
                        room: 'Dining Area',
                        class: 'subject-break'
                    },
                    {
                        activity: 'Snack Time',
                        teacher: 'Teacher Sarah',
                        room: 'Dining Area',
                        class: 'subject-break'
                    },
                    {
                        activity: 'Snack Time',
                        teacher: 'Teacher Sarah',
                        room: 'Dining Area',
                        class: 'subject-break'
                    },
                    {
                        activity: 'Snack Time',
                        teacher: 'Teacher Sarah',
                        room: 'Dining Area',
                        class: 'subject-break'
                    },
                    {
                        activity: 'Snack Time',
                        teacher: 'Teacher Sarah',
                        room: 'Dining Area',
                        class: 'subject-break'
                    }
                    ]
                },
                {
                    time: '9:30 - 10:00',
                    days: [{
                        activity: 'Numbers & Counting',
                        teacher: 'Teacher Sarah',
                        room: 'Learning Center',
                        class: 'subject-math'
                    },
                    {
                        activity: 'Phonics & Letters',
                        teacher: 'Teacher Sarah',
                        room: 'Learning Center',
                        class: 'subject-english'
                    },
                    {
                        activity: 'Shapes & Colors',
                        teacher: 'Teacher Sarah',
                        room: 'Learning Center',
                        class: 'subject-art'
                    },
                    {
                        activity: 'Nature Study',
                        teacher: 'Teacher Sarah',
                        room: 'Garden Area',
                        class: 'subject-science'
                    },
                    {
                        activity: 'Water Play',
                        teacher: 'Teacher Sarah',
                        room: 'Outdoor Area',
                        class: 'subject-pe'
                    }
                    ]
                },
                {
                    time: '10:00 - 10:30',
                    days: [{
                        activity: 'Outdoor Play',
                        teacher: 'Teacher Sarah',
                        room: 'Playground',
                        class: 'subject-pe'
                    },
                    {
                        activity: 'Dramatic Play',
                        teacher: 'Teacher Sarah',
                        room: 'Role Play Area',
                        class: 'subject-art'
                    },
                    {
                        activity: 'Sensory Activities',
                        teacher: 'Teacher Sarah',
                        room: 'Sensory Table',
                        class: 'subject-sensory'
                    },
                    {
                        activity: 'Group Games',
                        teacher: 'Teacher Sarah',
                        room: 'Activity Area',
                        class: 'subject-pe'
                    },
                    {
                        activity: 'Music & Dance',
                        teacher: 'Teacher Sarah',
                        room: 'Activity Area',
                        class: 'subject-music'
                    }
                    ]
                }
                ];
            }

            getPrimaryTimetable() {
                return [{
                    time: '8:00 - 8:45',
                    days: [{
                        subject: 'Mathematics',
                        teacher: 'Mr. Johnson',
                        room: 'Room 201',
                        class: 'subject-math'
                    },
                    {
                        subject: 'English',
                        teacher: 'Mr. Yusuf',
                        room: 'Room 105',
                        class: 'subject-english'
                    },
                    {
                        subject: 'Science',
                        teacher: 'Dr. Amina',
                        room: 'Lab 3',
                        class: 'subject-science'
                    },
                    {
                        subject: 'Mathematics',
                        teacher: 'Mr. Johnson',
                        room: 'Room 201',
                        class: 'subject-math'
                    },
                    {
                        subject: 'Social Studies',
                        teacher: 'Mr. Kabir',
                        room: 'Room 112',
                        class: 'subject-history'
                    }
                    ]
                },
                {
                    time: '8:45 - 9:30',
                    days: [{
                        subject: 'Science',
                        teacher: 'Dr. Amina',
                        room: 'Lab 2',
                        class: 'subject-science'
                    },
                    {
                        subject: 'Mathematics',
                        teacher: 'Mr. Johnson',
                        room: 'Room 201',
                        class: 'subject-math'
                    },
                    {
                        subject: 'English',
                        teacher: 'Mr. Yusuf',
                        room: 'Room 105',
                        class: 'subject-english'
                    },
                    {
                        subject: 'Biology',
                        teacher: 'Mrs. Fatima',
                        room: 'Lab 1',
                        class: 'subject-science'
                    },
                    {
                        subject: 'Mathematics',
                        teacher: 'Mr. Johnson',
                        room: 'Room 201',
                        class: 'subject-math'
                    }
                    ]
                },
                {
                    time: '9:30 - 10:15',
                    days: [{
                        subject: 'Civics',
                        teacher: 'Mr. Kabir',
                        room: 'Room 112',
                        class: 'subject-history'
                    },
                    {
                        subject: 'Physical Education',
                        teacher: 'Coach Ahmed',
                        room: 'Sports Field',
                        class: 'subject-pe'
                    },
                    {
                        subject: 'Art',
                        teacher: 'Mrs. Zainab',
                        room: 'Art Room',
                        class: 'subject-art'
                    },
                    {
                        subject: 'Physical Education',
                        teacher: 'Coach Ahmed',
                        room: 'Sports Field',
                        class: 'subject-pe'
                    },
                    {
                        subject: 'Science',
                        teacher: 'Dr. Amina',
                        room: 'Lab 3',
                        class: 'subject-science'
                    }
                    ]
                },
                {
                    time: '10:15 - 11:00',
                    days: [null, null, null, null, null] // Break period
                },
                {
                    time: '11:00 - 11:45',
                    days: [{
                        subject: 'Literature',
                        teacher: 'Mr. Yusuf',
                        room: 'Room 105',
                        class: 'subject-english'
                    },
                    {
                        subject: 'Science',
                        teacher: 'Dr. Amina',
                        room: 'Lab 2',
                        class: 'subject-science'
                    },
                    {
                        subject: 'Mathematics',
                        teacher: 'Mr. Johnson',
                        room: 'Room 201',
                        class: 'subject-math'
                    },
                    {
                        subject: 'Geography',
                        teacher: 'Mr. Kabir',
                        room: 'Room 112',
                        class: 'subject-history'
                    },
                    {
                        subject: 'English',
                        teacher: 'Mr. Yusuf',
                        room: 'Room 105',
                        class: 'subject-english'
                    }
                    ]
                }
                ];
            }

            getSecondaryTimetable() {
                // Similar structure but with secondary school subjects
                return this.getPrimaryTimetable();
            }

            addTimetableCellEvents() {
                document.querySelectorAll('.timetable-cell').forEach(cell => {
                    cell.addEventListener('click', function () {
                        const subject = this.querySelector('.font-semibold').textContent;
                        const teacher = this.querySelectorAll('p')[1]?.textContent || 'N/A';
                        const room = this.querySelectorAll('p')[2]?.textContent || 'N/A';

                        alert(`Subject: ${subject}\nTeacher: ${teacher}\nRoom: ${room}`);
                    });
                });
            }

            loadInitialData() {
                // Initial setup
                console.log('Timetable Manager initialized');
            }

            populateModalOptions() {
                // Populate modal with sample data
                const subjects = {
                    primary: ['Mathematics', 'English', 'Science', 'Social Studies', 'Art', 'Physical Education', 'Religious Studies'],
                    secondary: ['Mathematics', 'English', 'Physics', 'Chemistry', 'Biology', 'History', 'Geography', 'Economics', 'Art', 'Physical Education']
                };

                const teachers = {
                    primary: ['Mr. Johnson', 'Mr. Yusuf', 'Dr. Amina', 'Mr. Kabir', 'Mrs. Zainab', 'Coach Ahmed'],
                    secondary: ['Mr. Johnson', 'Mr. Yusuf', 'Dr. Amina', 'Mr. Kabir', 'Mrs. Zainab', 'Coach Ahmed', 'Mrs. Fatima', 'Mr. Bello']
                };

                const classrooms = ['Room 201', 'Room 105', 'Room 112', 'Lab 1', 'Lab 2', 'Lab 3', 'Art Room', 'Music Room', 'Sports Field'];

                // This would be populated dynamically based on user selection in a real app
            }

            updateModalClasses(level) {
                const classSelect = document.getElementById('class');
                classSelect.innerHTML = '<option value="">Select Class</option>';

                const classes = level === 'primary' ? ['Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6'] : ['JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'];

                classes.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.toLowerCase().replace(/\s+/g, '-');
                    option.textContent = cls;
                    classSelect.appendChild(option);
                });
            }

            updateModalSubjects(level) {
                const subjectSelect = document.getElementById('subject');
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';

                const subjects = level === 'primary' ? ['Mathematics', 'English', 'Science', 'Social Studies', 'Art', 'Physical Education', 'Religious Studies'] : ['Mathematics', 'English', 'Physics', 'Chemistry', 'Biology', 'History', 'Geography', 'Economics', 'Art', 'Physical Education'];

                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.toLowerCase().replace(/\s+/g, '-');
                    option.textContent = subject;
                    subjectSelect.appendChild(option);
                });
            }

            updateModalPeriods(level) {
                const periodSelect = document.getElementById('period');
                periodSelect.innerHTML = '<option value="">Select Period</option>';

                let periods;
                if (level === 'early-childhood') {
                    periods = this.periodsEarlyYears;
                } else if (level === 'primary') {
                    periods = this.periodsPrimary;
                } else {
                    periods = this.periodsSecondary;
                }

                periods.forEach((p, index) => {
                    const option = document.createElement('option');
                    option.value = p.name.toLowerCase().includes('break') ? 'break' : (index + 1);
                    option.dataset.start = p.start;
                    option.dataset.end = p.end;
                    option.textContent = `${p.name} (${p.start} - ${p.end})`;
                    periodSelect.appendChild(option);
                });
            }
        }

        // Initialize timetable manager
        const timetableManager = new TimetableManager();

        // Sidebar toggle functionality (guarded)
        const sidebarToggleEl = document.getElementById('sidebarToggle');
        if (sidebarToggleEl) {
            sidebarToggleEl.addEventListener('click', function () {
                const sidebarEl = document.querySelector('.sidebar');
                const mainEl = document.querySelector('.main-content');
                if (sidebarEl) sidebarEl.classList.toggle('collapsed');
                if (mainEl) mainEl.classList.toggle('expanded');
                document.querySelectorAll('.sidebar-text').forEach(el => {
                    el.classList.toggle('hidden');
                });
            });
        }

        // Mobile menu toggle (guarded)
        const mobileMenuToggleEl = document.getElementById('mobileMenuToggle');
        if (mobileMenuToggleEl) {
            mobileMenuToggleEl.addEventListener('click', function () {
                const sidebarEl = document.querySelector('.sidebar');
                if (sidebarEl) sidebarEl.classList.toggle('mobile-show');
            });
        }

        // Modal functionality
        const modal = document.getElementById('addScheduleModal');
        const addScheduleBtn = document.getElementById('addScheduleBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const scheduleForm = document.getElementById('scheduleForm');

        if (addScheduleBtn) {
            addScheduleBtn.addEventListener('click', function () {
                if (modal) modal.classList.add('active');
            });
        }

        function closeModalFunc() {
            modal.classList.remove('active');
            scheduleForm.reset();
        }

        closeModal.addEventListener('click', closeModalFunc);
        cancelBtn.addEventListener('click', closeModalFunc);

        // Close modal when clicking outside
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModalFunc();
            }
        });

        // Form submission
        scheduleForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const subject = document.getElementById('subject').value;
            const day = document.getElementById('day').value;
            const startTime = document.getElementById('startTime').value;

            // Simulate adding a schedule
            alert(`Schedule added for ${subject} on ${day} at ${startTime}`);
            closeModalFunc();
        });

        // Auto-generate timetable
        document.getElementById('generateTimetableBtn').addEventListener('click', function () {
            if (!timetableManager.currentClass) {
                alert('Please select a class first');
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
            this.disabled = true;

            setTimeout(() => {
                alert('Timetable successfully generated!');
                this.innerHTML = '<i class="fas fa-magic mr-2"></i> Auto-Generate';
                this.disabled = false;

                // Reload the timetable
                timetableManager.loadTimetable(timetableManager.currentClass);
            }, 2000);
        });

        // Template card click events
        document.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', function () {
                const templateName = this.querySelector('h4').textContent;
                alert(`Applying ${templateName} to current class`);
            });
        });

        // Bulk operation buttons
        document.querySelectorAll('.bulk-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const action = this.textContent.trim();
                alert(`Bulk action: ${action}`);
            });
        });

        // Export and Print functionality with matching CSS
        function generateStyledHTML(className, includeFooter) {
            const timetableContent = document.getElementById('timetableDisplay')?.innerHTML || '';
            if (!timetableContent) return null;

            let html = '<!DOCTYPE html><html><head>';
            html += '<meta charset="UTF-8">';
            html += '<title>' + className + ' - Timetable</title>';
            html += '<style>';
            // Import Tailwind and Font Awesome for styling
            html += '@import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap");';
            html += 'h2 { text-align: center; color: #1e3a8a; font-size: 16px; margin-bottom: 2px; margin-top: 0; font-weight: 600; }';
            html += 'p { text-align: center; color: #64748b; margin: 2px 0; font-size: 10px; }';
            html += '.timetable-header { background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 10px; border-radius: 8px; margin-bottom: 10px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }';
            html += '.timetable-header img { height: 60px; width: auto; margin-bottom: 10px; background: white; border-radius: 50%; padding: 5px; }';
            html += 'table { width: 100%; border-collapse: collapse; background: white; box-shadow: none; border-radius: 8px; overflow: hidden; font-size: 10px; }';
            html += 'thead { background-color: #3b82f6; }';
            html += 'th { background-color: #3b82f6; color: white; padding: 4px; text-align: center; font-weight: 600; border: 1px solid #2563eb; white-space: nowrap; }';
            html += 'td { padding: 2px 4px; text-align: center; border: 1px solid #e2e8f0; vertical-align: middle; }';
            html += 'tr:nth-child(even) td { background-color: #f0f9ff; }';
            html += 'tr:hover td { background-color: #dbeafe; }';
            html += '.timetable-cell { padding: 2px; border-radius: 4px; }';
            html += '.timetable-cell p { margin: 0; line-height: 1.2; }';
            html += '.timetable-cell p.font-semibold { font-weight: 600; font-size: 10px; }';
            html += '.timetable-cell p.text-sm { font-size: 9px; }';
            html += '.timetable-cell p.text-xs { font-size: 8px; }';
            html += '.subject-math { background-color: #bfdbfe; border-left: 2px solid #3b82f6; }';
            html += '.subject-science { background-color: #bbf7d0; border-left: 2px solid #10b981; }';
            html += '.subject-english { background-color: #fde68a; border-left: 2px solid #f59e0b; }';
            html += '.subject-history { background-color: #e9d5ff; border-left: 2px solid #8b5cf6; }';
            html += '.subject-art { background-color: #fecaca; border-left: 2px solid #ef4444; }';
            html += '.subject-pe { background-color: #c7d2fe; border-left: 2px solid #6366f1; }';
            html += '.subject-religious { background-color: #ddd6fe; border-left: 2px solid #7c3aed; }';
            html += '.subject-music { background-color: #f0abfc; border-left: 2px solid #c026d3; }';
            html += '.subject-break { background-color: #fef3c7; border-left: 2px solid #f59e0b; }';
            html += '.subject-sensory { background-color: #d1fae5; border-left: 2px solid #10b981; }';
            html += '.subject-story { background-color: #e0e7ff; border-left: 2px solid #4f46e5; }';
            html += '.subject-computer { background-color: #a7f3d0; border-left: 2px solid #059669; }';
            html += '.time-cell { background-color: #f0f9ff; font-weight: 600; color: #1e40af; }';
            html += '.footer { margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 10px; color: #94a3b8; }';
            html += '@media print { @page { size: landscape; margin: 5mm; } body { padding: 0; } table { box-shadow: none; width: 100%; table-layout: fixed; } tr:hover td { background-color: inherit; } }';
            html += '</style>';
            html += '</head><body>';

            // Resolve logo URL
            const logoUrl = new URL('../school_logo.png', window.location.href).href;

            html += '<div class="timetable-header">';
            html += '<img src="' + logoUrl + '" alt="School Logo">';
            html += '<h1>' + SCHOOL_NAME + '</h1>';
            html += '<h2>' + className + ' - Timetable</h2>';
            html += '<p>Academic Year 2023/2024 | Term 3</p>';
            html += '</div>';
            html += timetableContent;
            if (includeFooter) {
                html += '<div class="footer">';
                html += '<p>Generated on: ' + new Date().toLocaleString() + '</p>';
                html += '<p>© 2024 Northland Schools Kano. All rights reserved.</p>';
                html += '</div>';
            }
            html += '</body></html>';
            return html;
        }

        document.getElementById('exportPdfBtn')?.addEventListener('click', function () {
            if (!timetableManager.currentClass) {
                alert('Please select a class first');
                return;
            }

            const className = document.querySelector('.class-selector option:checked')?.text || 'Timetable';
            const htmlContent = generateStyledHTML(className, true);

            if (!htmlContent) {
                alert('No timetable data to export');
                return;
            }

            const printWindow = window.open('', '', 'width=1000,height=800');
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 800);
        });

        document.getElementById('printBtn')?.addEventListener('click', function () {
            if (!timetableManager.currentClass) {
                alert('Please select a class first');
                return;
            }

            const className = document.querySelector('.class-selector option:checked')?.text || 'Timetable';
            const htmlContent = generateStyledHTML(className, true);

            if (!htmlContent) {
                alert('No timetable data to print');
                return;
            }

            const printWindow = window.open('', '', 'width=1000,height=800');
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
            }, 800);
        });

        document.getElementById('exportExcelBtn')?.addEventListener('click', function () {
            if (!timetableManager.currentClass) {
                alert('Please select a class first');
                return;
            }

            const className = document.querySelector('.class-selector option:checked')?.text || 'Timetable';
            const table = document.querySelector('#timetableDisplay table');

            if (!table) {
                alert('No timetable data to export');
                return;
            }

            let csv = SCHOOL_NAME + '\n' + className + ' - Timetable\n\n';
            csv += 'Exported on: ' + new Date().toLocaleString() + '\n\n';

            const rows = table.querySelectorAll('tr');
            rows.forEach(function (row) {
                const cells = row.querySelectorAll('th, td');
                const rowData = Array.from(cells)
                    .map(function (cell) {
                        const text = cell.textContent.trim().replace(/"/g, '""').replace(/,/g, ';');
                        return '"' + text + '"';
                    })
                    .join(',');
                csv += rowData + '\n';
            });

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            const filename = className.replace(/\s+/g, '_') + '_timetable_' + new Date().getTime() + '.csv';
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });

        // Daily view day selector
        document.addEventListener('click', function (e) {
            if (e.target && e.target.id === 'dailyDaySelector') {
                if (timetableManager.currentClass && timetableManager.currentView === 'daily') {
                    timetableManager.loadTimetable(timetableManager.currentClass);
                }
            }
        });

        // Import Functionality
        document.getElementById('importTimetableBtn')?.addEventListener('click', function () {
            document.getElementById('importCsvInput').click();
        });

        document.getElementById('importCsvInput')?.addEventListener('change', function (e) {
            if (!this.files || !this.files.length) return;

            const file = this.files[0];
            const formData = new FormData();
            formData.append('csvFile', file);

            // Show loading state
            const btn = document.getElementById('importTimetableBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';
            btn.disabled = true;

            fetch('import_timetable_process.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let msg = 'Import successful! Imported ' + data.imported_count + ' entries.';
                        if (data.errors && data.errors.length > 0) {
                            msg += '\n\nWarnings:\n' + data.errors.slice(0, 10).join('\n') + (data.errors.length > 10 ? '\n...and more.' : '');
                        }
                        alert(msg);

                        // Refresh timetable if class is selected
                        if (timetableManager.currentClass) {
                            timetableManager.loadTimetable(timetableManager.currentClass);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert('Import failed: ' + data.message + (data.errors ? '\nErrors:\n' + data.errors.slice(0, 10).join('\n') : ''));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during import.');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    document.getElementById('importCsvInput').value = ''; // Reset
                });
        });
    </script>
</body>

</html>