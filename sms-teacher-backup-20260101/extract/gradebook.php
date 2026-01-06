<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gradebook - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
        
        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        
        .dashboard-card {
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar {
            transition: all var(--transition-speed) ease;
            width: var(--sidebar-width);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .main-content {
            transition: all var(--transition-speed) ease;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                z-index: 20;
            }
            
            .sidebar.mobile-show {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 15;
            }
            
            .mobile-overlay.active {
                display: block;
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
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .floating-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .floating-btn:hover {
            transform: scale(1.1);
        }
        
        .sidebar-link.active {
            background-color: #1e40af !important;
        }
        
        .mobile-header {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .desktop-header {
                display: none;
            }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: scale(1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .grade-input {
            width: 60px;
            text-align: center;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 14px;
        }
        
        .grade-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .grade-excellent { background-color: #dcfce7; color: #166534; }
        .grade-good { background-color: #f0f9ff; color: #1e40af; }
        .grade-average { background-color: #fef3c7; color: #92400e; }
        .grade-poor { background-color: #fef2f2; color: #991b1b; }
    </style>
</head>
<body class="flex">
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar bg-nsknavy text-white h-screen fixed top-0 left-0 z-10">
        <div class="p-5">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-2">
                    <div class="logo-container w-10 h-10 rounded-full flex items-center justify-center text-white font-bold">
                        NSK
                    </div>
                    <h1 class="text-xl font-bold sidebar-text">NORTHLAND SCHOOLS</h1>
                </div>
                <button id="sidebarToggle" class="text-white">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="space-y-2">
                <a href="teacher dashboard.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-tachometer-alt mr-3"></i> <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="my-classes.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-chalkboard mr-3"></i> <span class="sidebar-text">My Classes</span>
                </a>
                <a href="my-students.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-user-graduate mr-3"></i> <span class="sidebar-text">Students</span>
                </a>
                <!-- <a href="gradebook.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg bg-nskblue transition active">
                    <i class="fas fa-book-open mr-3"></i> <span class="sidebar-text">Gradebook</span>
                </a> -->
                <a href="assignments.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-tasks mr-3"></i> <span class="sidebar-text">Assignments</span>
                </a>
                <a href="attendance.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-clipboard-check mr-3"></i> <span class="sidebar-text">Attendance</span>
                </a>
                <a href="results.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-chart-bar mr-3"></i> <span class="sidebar-text">Results</span>
                </a>
                <!-- <a href="messages.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
                    <i class="fas fa-comments mr-3"></i> <span class="sidebar-text">Messages</span>
                </a> -->
            </nav>
        </div>
        
        <div class="absolute bottom-0 w-full p-5">
            <div class="flex items-center space-x-3 bg-nskblue p-3 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center">
                    <span class="font-bold">JA</span>
                </div>
                <div class="sidebar-text">
                    <p class="text-sm font-semibold">Mr. Johnson Adeyemi</p>
                    <p class="text-xs opacity-80">Mathematics Teacher</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Desktop Header -->
        <header class="desktop-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Gradebook</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search students..." class="bg-transparent outline-none w-32 md:w-64">
                        </div>
                    </div>
                    
                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>
                    
                    <div class="hidden md:flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                            JA
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-nsknavy">Mr. Johnson Adeyemi</p>
                            <p class="text-xs text-gray-600">Mathematics Teacher</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Header -->
        <header class="mobile-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">Gradebook</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                            JA
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Gradebook Content -->
        <div class="p-4 md:p-6">
            <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 md:mb-6 space-y-3 md:space-y-0">
                    <h2 class="text-lg md:text-xl font-bold text-nsknavy">Gradebook</h2>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        <select id="gradeClassFilter" class="px-3 py-2 border rounded-lg text-sm">
                            <option value="10A">Grade 10A</option>
                            <option value="10B">Grade 10B</option>
                            <option value="11A">Grade 11A</option>
                            <option value="9A">Grade 9A</option>
                        </select>
                        <select id="gradeAssignmentFilter" class="px-3 py-2 border rounded-lg text-sm">
                            <option value="">All Assignments</option>
                            <option value="quiz1">Quiz 1: Algebra</option>
                            <option value="quiz2">Quiz 2: Geometry</option>
                            <option value="midterm">Midterm Exam</option>
                            <option value="project">Final Project</option>
                        </select>
                        <button id="addGradeBtn" class="bg-nskgreen text-white px-3 py-2 rounded-lg hover:bg-green-600 transition text-sm">
                            <i class="fas fa-plus mr-2"></i>Add Grade
                        </button>
                        <button id="exportGradesBtn" class="bg-nskgold text-white px-3 py-2 rounded-lg hover:bg-amber-600 transition text-sm">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Grade Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskblue">84%</div>
                        <p class="text-xs text-gray-600">Class Average</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskgreen">92%</div>
                        <p class="text-xs text-gray-600">Highest Grade</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskgold">68%</div>
                        <p class="text-xs text-gray-600">Lowest Grade</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskred">3</div>
                        <p class="text-xs text-gray-600">Need Improvement</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm md:text-base">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold">Student</th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold">Quiz 1<br><span class="text-xs font-normal">Algebra</span></th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold">Quiz 2<br><span class="text-xs font-normal">Geometry</span></th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold hidden md:table-cell">Midterm<br><span class="text-xs font-normal">Exam</span></th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold hidden lg:table-cell">Project<br><span class="text-xs font-normal">Final</span></th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold">Average</th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold">Grade</th>
                                <th class="py-3 px-3 md:px-6 text-center text-nsknavy font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- Student 1 -->
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskblue">AI</div>
                                        <div class="ml-3">
                                            <p class="font-semibold text-sm md:text-base">Ahmed Ibrahim</p>
                                            <p class="text-xs text-gray-600">ID: STU-2023-001</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="85" data-student="STU-2023-001" data-assignment="quiz1">
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="88" data-student="STU-2023-001" data-assignment="quiz2">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden md:table-cell">
                                    <input type="text" class="grade-input" value="90" data-student="STU-2023-001" data-assignment="midterm">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden lg:table-cell">
                                    <input type="text" class="grade-input" value="87" data-student="STU-2023-001" data-assignment="project">
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="text-nskgreen font-bold">87.5%</span>
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold grade-excellent">B+</span>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex justify-center space-x-2">
                                        <button class="save-grade text-nskblue hover:text-nsknavy" data-student="STU-2023-001">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="text-nskgreen hover:text-green-700">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Student 2 -->
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskgreen">FO</div>
                                        <div class="ml-3">
                                            <p class="font-semibold text-sm md:text-base">Fatima Okafor</p>
                                            <p class="text-xs text-gray-600">ID: STU-2023-002</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="92" data-student="STU-2023-002" data-assignment="quiz1">
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="95" data-student="STU-2023-002" data-assignment="quiz2">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden md:table-cell">
                                    <input type="text" class="grade-input" value="94" data-student="STU-2023-002" data-assignment="midterm">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden lg:table-cell">
                                    <input type="text" class="grade-input" value="93" data-student="STU-2023-002" data-assignment="project">
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="text-nskgreen font-bold">93.5%</span>
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold grade-excellent">A</span>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex justify-center space-x-2">
                                        <button class="save-grade text-nskblue hover:text-nsknavy" data-student="STU-2023-002">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="text-nskgreen hover:text-green-700">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Student 3 -->
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskgold">CY</div>
                                        <div class="ml-3">
                                            <p class="font-semibold text-sm md:text-base">Chinedu Yusuf</p>
                                            <p class="text-xs text-gray-600">ID: STU-2023-003</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="72" data-student="STU-2023-003" data-assignment="quiz1">
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="78" data-student="STU-2023-003" data-assignment="quiz2">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden md:table-cell">
                                    <input type="text" class="grade-input" value="75" data-student="STU-2023-003" data-assignment="midterm">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden lg:table-cell">
                                    <input type="text" class="grade-input" value="70" data-student="STU-2023-003" data-assignment="project">
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="text-nskgold font-bold">73.8%</span>
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold grade-average">C</span>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex justify-center space-x-2">
                                        <button class="save-grade text-nskblue hover:text-nsknavy" data-student="STU-2023-003">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="text-nskgreen hover:text-green-700">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Student 4 -->
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskred">AA</div>
                                        <div class="ml-3">
                                            <p class="font-semibold text-sm md:text-base">Amina Abdullahi</p>
                                            <p class="text-xs text-gray-600">ID: STU-2023-004</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="65" data-student="STU-2023-004" data-assignment="quiz1">
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="62" data-student="STU-2023-004" data-assignment="quiz2">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden md:table-cell">
                                    <input type="text" class="grade-input" value="68" data-student="STU-2023-004" data-assignment="midterm">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden lg:table-cell">
                                    <input type="text" class="grade-input" value="70" data-student="STU-2023-004" data-assignment="project">
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="text-nskred font-bold">66.3%</span>
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold grade-poor">D</span>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex justify-center space-x-2">
                                        <button class="save-grade text-nskblue hover:text-nsknavy" data-student="STU-2023-004">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="text-nskgreen hover:text-green-700">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Student 5 -->
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-purple-500">SE</div>
                                        <div class="ml-3">
                                            <p class="font-semibold text-sm md:text-base">Samuel Eze</p>
                                            <p class="text-xs text-gray-600">ID: STU-2023-005</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="89" data-student="STU-2023-005" data-assignment="quiz1">
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <input type="text" class="grade-input" value="91" data-student="STU-2023-005" data-assignment="quiz2">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden md:table-cell">
                                    <input type="text" class="grade-input" value="94" data-student="STU-2023-005" data-assignment="midterm">
                                </td>
                                <td class="py-4 px-3 md:px-6 hidden lg:table-cell">
                                    <input type="text" class="grade-input" value="96" data-student="STU-2023-005" data-assignment="project">
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="text-nskgreen font-bold">92.5%</span>
                                </td>
                                <td class="py-4 px-3 md:px-6 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold grade-excellent">A</span>
                                </td>
                                <td class="py-4 px-3 md:px-6">
                                    <div class="flex justify-center space-x-2">
                                        <button class="save-grade text-nskblue hover:text-nsknavy" data-student="STU-2023-005">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="text-nskgreen hover:text-green-700">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 md:mt-6 flex justify-between items-center">
                    <p class="text-sm text-gray-600">Showing 5 of 30 students</p>
                    <div class="flex space-x-3">
                        <button class="px-3 py-1 bg-gray-200 rounded-lg text-sm hover:bg-gray-300 transition">Previous</button>
                        <button id="saveAllGradesBtn" class="bg-nskblue text-white px-4 py-2 rounded-lg hover:bg-nsknavy transition text-sm">
                            <i class="fas fa-save mr-2"></i>Save All Changes
                        </button>
                        <button class="px-3 py-1 bg-nskgreen text-white rounded-lg text-sm hover:bg-green-600 transition">Next</button>
                    </div>
                </div>
            </div>

            <!-- Grade Distribution -->
            <div class="bg-white rounded-xl shadow-md p-4 md:p-6 mt-6">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Grade Distribution</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-nskgreen mb-1">A</div>
                        <div class="text-lg font-semibold">90-100%</div>
                        <div class="text-sm text-gray-600">8 Students</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-nskblue mb-1">B</div>
                        <div class="text-lg font-semibold">80-89%</div>
                        <div class="text-sm text-gray-600">12 Students</div>
                    </div>
                    <div class="text-center p-4 bg-amber-50 rounded-lg">
                        <div class="text-2xl font-bold text-nskgold mb-1">C</div>
                        <div class="text-lg font-semibold">70-79%</div>
                        <div class="text-sm text-gray-600">7 Students</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <div class="text-2xl font-bold text-nskred mb-1">D</div>
                        <div class="text-lg font-semibold">60-69%</div>
                        <div class="text-sm text-gray-600">2 Students</div>
                    </div>
                    <div class="text-center p-4 bg-gray-100 rounded-lg">
                        <div class="text-2xl font-bold text-gray-600 mb-1">F</div>
                        <div class="text-lg font-semibold">Below 60%</div>
                        <div class="text-sm text-gray-600">1 Student</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Action Button for Mobile -->
    <button class="floating-btn md:hidden bg-nskblue text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center">
        <i class="fas fa-plus text-xl"></i>
    </button>

    <!-- Add Grade Modal -->
    <div id="addGradeModal" class="modal">
        <div class="modal-content w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy">Add New Grade</h3>
                <button id="closeAddGradeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                    <select class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                        <option>Select Student</option>
                        <option>Ahmed Ibrahim</option>
                        <option>Fatima Okafor</option>
                        <option>Chinedu Yusuf</option>
                        <option>Amina Abdullahi</option>
                        <option>Samuel Eze</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignment</label>
                    <select class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                        <option>Quiz 1: Algebra</option>
                        <option>Quiz 2: Geometry</option>
                        <option>Midterm Exam</option>
                        <option>Final Project</option>
                        <option>Homework 1</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                    <input type="number" min="0" max="100" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="Enter grade (0-100)">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comments (Optional)</label>
                    <textarea rows="3" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="Add comments about this grade"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancelAddGrade" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-nskgreen text-white rounded-lg text-sm hover:bg-green-600 transition">Add Grade</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // DOM Elements
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const gradeInputs = document.querySelectorAll('.grade-input');
        const saveGradeBtns = document.querySelectorAll('.save-grade');
        const saveAllGradesBtn = document.getElementById('saveAllGradesBtn');
        const addGradeBtn = document.getElementById('addGradeBtn');
        const addGradeModal = document.getElementById('addGradeModal');
        const closeAddGradeModal = document.getElementById('closeAddGradeModal');
        const cancelAddGrade = document.getElementById('cancelAddGrade');
        const gradeClassFilter = document.getElementById('gradeClassFilter');
        const gradeAssignmentFilter = document.getElementById('gradeAssignmentFilter');

        // Sidebar Toggle Functionality
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            const sidebarTexts = document.querySelectorAll('.sidebar-text');
            sidebarTexts.forEach(text => {
                text.classList.toggle('hidden');
            });
        }

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('active');
        }

        // Modal Functions
        function openModal(modal) {
            modal.classList.add('active');
        }

        function closeModalFunc(modal) {
            modal.classList.remove('active');
        }

        // Save individual grade
        function handleSaveGrade(e) {
            const studentId = e.target.closest('button').getAttribute('data-student');
            const row = e.target.closest('tr');
            const inputs = row.querySelectorAll('.grade-input');
            
            let grades = [];
            inputs.forEach(input => {
                grades.push({
                    assignment: input.getAttribute('data-assignment'),
                    grade: input.value
                });
            });
            
            // In a real app, this would send data to the server
            console.log(`Saving grades for student ${studentId}:`, grades);
            
            // Show success feedback
            showNotification('Grades saved successfully!', 'success');
        }

        // Save all grades
        function handleSaveAllGrades() {
            const allGrades = [];
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const studentId = row.querySelector('.save-grade').getAttribute('data-student');
                const inputs = row.querySelectorAll('.grade-input');
                
                const studentGrades = {
                    studentId: studentId,
                    grades: []
                };
                
                inputs.forEach(input => {
                    studentGrades.grades.push({
                        assignment: input.getAttribute('data-assignment'),
                        grade: input.value
                    });
                });
                
                allGrades.push(studentGrades);
            });
            
            // In a real app, this would send data to the server
            console.log('Saving all grades:', allGrades);
            
            // Show success feedback
            showNotification('All grades saved successfully!', 'success');
        }

        // Show notification
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Update grade colors based on value
        function updateGradeColors() {
            gradeInputs.forEach(input => {
                const value = parseInt(input.value);
                if (value >= 90) {
                    input.classList.add('grade-excellent');
                    input.classList.remove('grade-good', 'grade-average', 'grade-poor');
                } else if (value >= 80) {
                    input.classList.add('grade-good');
                    input.classList.remove('grade-excellent', 'grade-average', 'grade-poor');
                } else if (value >= 70) {
                    input.classList.add('grade-average');
                    input.classList.remove('grade-excellent', 'grade-good', 'grade-poor');
                } else {
                    input.classList.add('grade-poor');
                    input.classList.remove('grade-excellent', 'grade-good', 'grade-average');
                }
            });
        }

        // Event Listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);

        // Grade management
        saveGradeBtns.forEach(btn => {
            btn.addEventListener('click', handleSaveGrade);
        });

        saveAllGradesBtn.addEventListener('click', handleSaveAllGrades);

        // Grade input changes
        gradeInputs.forEach(input => {
            input.addEventListener('input', updateGradeColors);
            input.addEventListener('change', updateGradeColors);
        });

        // Add grade modal
        addGradeBtn.addEventListener('click', () => openModal(addGradeModal));
        closeAddGradeModal.addEventListener('click', () => closeModalFunc(addGradeModal));
        cancelAddGrade.addEventListener('click', () => closeModalFunc(addGradeModal));

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === addGradeModal) {
                closeModalFunc(addGradeModal);
            }
        });

        // Responsive adjustments
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-show');
                mobileOverlay.classList.remove('active');
            }
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', () => {
            updateGradeColors();
            console.log('Gradebook page loaded successfully');
        });
    </script>
</body>
</html>