<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Include sidebar CSS -->
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
        
        .attendance-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .attendance-card:hover {
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
        
        .attendance-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .attendance-table th {
            background-color: #f8fafc;
        }
        
        .attendance-table tr:last-child td {
            border-bottom: 0;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .attendance-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .present-dot {
            background-color: #10b981;
        }
        
        .absent-dot {
            background-color: #ef4444;
        }
        
        .late-dot {
            background-color: #f59e0b;
        }
        
        .attendance-checkbox {
            transform: scale(1.5);
            accent-color: #10b981;
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
            pointer-events: auto;
        }
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background-color: #1e40af;
            color: white;
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
            transition: width 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        .pulse-alert {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
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
        
        .filter-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
        }
        
        .attendance-summary {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
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
        <!-- Include sidebar.js -->
    <?php require_once 'sidebar.php'; ?>


    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy sidebar-toggle">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Attendance Management</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" placeholder="Search attendance..." class="bg-transparent outline-none w-32 md:w-64">
                        </div>
                    </div>
                    
                    <div class="relative">
                        <i class="fas fa-bell text-nsknavy text-xl"></i>
                        <div class="notification-dot"></div>
                    </div>
                    
                    <div class="hidden md:flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                            A
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-nsknavy">Admin User</p>
                            <p class="text-xs text-gray-600">Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Attendance Management Content -->
        <div class="p-6">
            <!-- Quick Stats -->
            <div class="quick-stats mb-8">
                <div class="attendance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-user-check text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Today's Attendance</p>
                        <p class="text-2xl font-bold text-nsknavy">94%</p>
                        <p class="text-xs text-nskgreen">1,170 of 1,245 students</p>
                    </div>
                </div>
                
                <div class="attendance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskblue p-4 rounded-full mr-4">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Monthly Average</p>
                        <p class="text-2xl font-bold text-nsknavy">89%</p>
                        <p class="text-xs text-gray-600">November 2023</p>
                    </div>
                </div>
                
                <div class="attendance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Late Arrivals</p>
                        <p class="text-2xl font-bold text-nsknavy">42</p>
                        <p class="text-xs text-nskgold">Today</p>
                    </div>
                </div>
                
                <div class="attendance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-user-times text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Absent Today</p>
                        <p class="text-2xl font-bold text-nsknavy">75</p>
                        <p class="text-xs text-nskred">5% of students</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="filter-section p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-nsknavy">Attendance Records</h2>
                    
                    <div class="flex flex-wrap gap-4">
                        <select class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">All Grades</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                        
                        <select class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">All Classes</option>
                            <option value="a">Section A</option>
                            <option value="b">Section B</option>
                            <option value="c">Section C</option>
                        </select>
                        
                        <input type="date" class="px-4 py-2 border rounded-lg form-input focus:border-nskblue" value="2023-11-20">
                        
                        <button class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        
                        <button id="takeAttendanceBtn" class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                            <i class="fas fa-clipboard-check mr-2"></i> Take Attendance
                        </button>
                        
                        <button class="export-btn px-4 py-2 rounded-lg font-semibold transition flex items-center">
                            <i class="fas fa-file-export mr-2"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="attendance-summary p-6 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold">Today's Summary</h2>
                        <p class="text-blue-100">Monday, November 20, 2023</p>
                    </div>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <div class="text-center">
                            <p class="text-2xl font-bold">94%</p>
                            <p class="text-blue-100 text-sm">Overall</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold">96%</p>
                            <p class="text-blue-100 text-sm">Secondary</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold">91%</p>
                            <p class="text-blue-100 text-sm">Primary</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Tabs -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="flex flex-wrap gap-2 mb-6">
                    <button class="tab-button px-4 py-2 rounded-lg border border-nskblue text-nskblue font-semibold active">
                        Daily View
                    </button>
                    <button class="tab-button px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold">
                        Monthly View
                    </button>
                    <button class="tab-button px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold">
                        Student Reports
                    </button>
                    <button class="tab-button px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold">
                        Analytics
                    </button>
                </div>
                
                <!-- Daily Attendance Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full attendance-table">
                        <thead>
                            <tr>
                                <th class="py-3 px-6 text-left text-nsknavy">Student</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Grade/Class</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Status</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Time</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Remarks</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="fade-in">
                                <td class="py-4 px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskblue mr-3">
                                            AA
                                        </div>
                                        <div>
                                            <p class="font-semibold">Ahmad Abdullahi</p>
                                            <p class="text-sm text-gray-600">ID: NSK-2023-001</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Grade 10-B</p>
                                    <p class="text-sm text-gray-600">Roll No: 15</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-green-100 text-nskgreen">
                                        <span class="attendance-dot present-dot"></span> Present
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">08:05 AM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm text-gray-600">-</p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr class="fade-in">
                                <td class="py-4 px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskgreen mr-3">
                                            FM
                                        </div>
                                        <div>
                                            <p class="font-semibold">Fatima Mohammed</p>
                                            <p class="text-sm text-gray-600">ID: NSK-2023-012</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Grade 9-A</p>
                                    <p class="text-sm text-gray-600">Roll No: 8</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-yellow-100 text-yellow-700">
                                        <span class="attendance-dot late-dot"></span> Late
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">08:42 AM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm text-gray-600">Traffic delay</p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr class="fade-in">
                                <td class="py-4 px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskgold mr-3">
                                            YI
                                        </div>
                                        <div>
                                            <p class="font-semibold">Yusuf Ibrahim</p>
                                            <p class="text-sm text-gray-600">ID: NSK-2023-045</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Grade 11-C</p>
                                    <p class="text-sm text-gray-600">Roll No: 22</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-green-100 text-nskgreen">
                                        <span class="attendance-dot present-dot"></span> Present
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">08:15 AM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm text-gray-600">-</p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr class="fade-in">
                                <td class="py-4 px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-nskred mr-3">
                                            HA
                                        </div>
                                        <div>
                                            <p class="font-semibold">Hauwa Abubakar</p>
                                            <p class="text-sm text-gray-600">ID: NSK-2023-078</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Grade 8-B</p>
                                    <p class="text-sm text-gray-600">Roll No: 17</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-red-100 text-nskred">
                                        <span class="attendance-dot absent-dot"></span> Absent
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">-</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm text-gray-600">Illness</p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr class="fade-in">
                                <td class="py-4 px-6">
                                    <div class="flex items-center">
                                        <div class="student-avatar bg-purple-500 mr-3">
                                            ZM
                                        </div>
                                        <div>
                                            <p class="font-semibold">Zainab Mohammed</p>
                                            <p class="text-sm text-gray-600">ID: NSK-2023-103</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Grade 12-A</p>
                                    <p class="text-sm text-gray-600">Roll No: 5</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-green-100 text-nskgreen">
                                        <span class="attendance-dot present-dot"></span> Present
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">07:58 AM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm text-gray-600">-</p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex justify-between items-center mt-6">
                    <p class="text-gray-600">Showing 1 to 5 of 1245 entries</p>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 border rounded-lg text-gray-700 hover:bg-gray-50">Previous</button>
                        <button class="px-3 py-1 border rounded-lg bg-nskblue text-white">1</button>
                        <button class="px-3 py-1 border rounded-lg text-gray-700 hover:bg-gray-50">2</button>
                        <button class="px-3 py-1 border rounded-lg text-gray-700 hover:bg-gray-50">3</button>
                        <button class="px-3 py-1 border rounded-lg text-gray-700 hover:bg-gray-50">Next</button>
                    </div>
                </div>
            </div>

            <!-- Attendance Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="attendance-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Attendance Overview</h2>
                    <canvas id="attendanceChart" height="250"></canvas>
                </div>
                
                <div class="attendance-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Class-wise Attendance</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Grade 10-A</span>
                                <span class="font-semibold">96%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskgreen" style="width: 96%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Grade 9-B</span>
                                <span class="font-semibold">92%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskblue" style="width: 92%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Grade 11-C</span>
                                <span class="font-semibold">89%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskgold" style="width: 89%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Grade 8-A</span>
                                <span class="font-semibold">85%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-amber-500" style="width: 85%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Grade 7-B</span>
                                <span class="font-semibold">82%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskred" style="width: 82%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Alerts -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-nsknavy mb-6">Attendance Alerts</h2>
                
                <div class="space-y-4">
                    <div class="flex items-start p-4 border-l-4 border-nskred bg-red-50 rounded-r-lg pulse-alert">
                        <div class="bg-nskred p-2 rounded-full mr-4 mt-1">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold">Low Attendance Alert</p>
                            <p class="text-sm text-gray-600">Grade 7-B has attendance below 85% for the past 5 days</p>
                            <p class="text-xs text-gray-500">20 Nov 2023, 10:30 AM</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start p-4 border-l-4 border-nskgold bg-amber-50 rounded-r-lg">
                        <div class="bg-nskgold p-2 rounded-full mr-4 mt-1">
                            <i class="fas fa-user-clock text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold">Frequent Late Arrivals</p>
                            <p class="text-sm text-gray-600">Ahmad Abdullahi (Grade 10-B) has been late 3 times this week</p>
                            <p class="text-xs text-gray-500">19 Nov 2023, 03:15 PM</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start p-4 border-l-4 border-nskblue bg-blue-50 rounded-r-lg">
                        <div class="bg-nskblue p-2 rounded-full mr-4 mt-1">
                            <i class="fas fa-info-circle text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold">Attendance Report Generated</p>
                            <p class="text-sm text-gray-600">Monthly attendance report for October 2023 has been generated</p>
                            <p class="text-xs text-gray-500">18 Nov 2023, 09:45 AM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Take Attendance Modal -->
    <div id="attendanceModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-nsknavy">Take Attendance</h2>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Select Grade</label>
                        <select class="w-full px-4 py-2 border rounded-lg focus:border-nskblue">
                            <option value="">Select Grade</option>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Select Class</label>
                        <select class="w-full px-4 py-2 border rounded-lg focus:border-nskblue">
                            <option value="">Select Class</option>
                            <option value="a">Section A</option>
                            <option value="b">Section B</option>
                            <option value="c">Section C</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Date</label>
                        <input type="date" class="w-full px-4 py-2 border rounded-lg focus:border-nskblue" value="2023-11-20">
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Grade 10-B Students (32 students)</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 text-left text-nsknavy">Student Name</th>
                                    <th class="py-3 px-4 text-left text-nsknavy">Roll No</th>
                                    <th class="py-3 px-4 text-left text-nsknavy">Status</th>
                                    <th class="py-3 px-4 text-left text-nsknavy">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="student-avatar bg-nskblue mr-3">
                                                AA
                                            </div>
                                            <div>
                                                <p class="font-medium">Ahmad Abdullahi</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">15</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-4">
                                            <label class="flex items-center">
                                                <input type="radio" name="status1" class="attendance-checkbox mr-2" checked>
                                                <span class="text-sm">Present</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="status1" class="attendance-checkbox mr-2">
                                                <span class="text-sm">Absent</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="status1" class="attendance-checkbox mr-2">
                                                <span class="text-sm">Late</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full px-3 py-1 border rounded text-sm" placeholder="Add remarks">
                                    </td>
                                </tr>
                                
                                <tr class="border-b">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="student-avatar bg-nskgreen mr-3">
                                                FM
                                            </div>
                                            <div>
                                                <p class="font-medium">Fatima Mohammed</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">8</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-4">
                                            <label class="flex items-center">
                                                <input type="radio" name="status2" class="attendance-checkbox mr-2">
                                                <span class="text-sm">Present</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="status2" class="attendance-checkbox mr-2" checked>
                                                <span class="text-sm">Absent</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="status2" class="attendance-checkbox mr-2">
                                                <span class="text-sm">Late</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full px-3 py-1 border rounded text-sm" placeholder="Add remarks" value="Illness">
                                    </td>
                                </tr>
                                
                                <tr class="border-b">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="student-avatar bg-nskgold mr-3">
                                                YI
                                            </div>
                                            <div>
                                                <p class="font-medium">Yusuf Ibrahim</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">22</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-4">
                                            <label class="flex items-center">
                                                <input type="radio" name="status3" class="attendance-checkbox mr-2" checked>
                                                <span class="text-sm">Present</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="status3" class="attendance-checkbox mr-2">
                                                <span class="text-sm">Absent</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="status3" class="attendance-checkbox mr-2">
                                                <span class="text-sm">Late</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full px-3 py-1 border rounded text-sm" placeholder="Add remarks">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button id="cancelAttendance" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button class="px-6 py-2 bg-nskblue text-white rounded-lg hover:bg-nsknavy">
                        Save Attendance
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-action-btn w-14 h-14 bg-nskblue text-white rounded-full flex items-center justify-center shadow-lg hover:bg-nsknavy transition">
        <i class="fas fa-plus text-xl"></i>
    </button>
    <!-- Include footer -->
<script src="footer.js"></script>

    <script>
        // Initialize sidebar with current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            if (window.sidebarManager) {
                window.sidebarManager.init(currentPage);
            }
            
            // Initialize the rest of the page functionality
            initializeAttendancePage();
        });

        function initializeAttendancePage() {
            // DOM Elements
            const takeAttendanceBtn = document.getElementById('takeAttendanceBtn');
            const attendanceModal = document.getElementById('attendanceModal');
            const closeModal = document.getElementById('closeModal');
            const cancelAttendance = document.getElementById('cancelAttendance');
            const tabButtons = document.querySelectorAll('.tab-button');
            
            // Open Attendance Modal
            takeAttendanceBtn.addEventListener('click', () => {
                attendanceModal.classList.add('active');
            });
            
            // Close Attendance Modal
            closeModal.addEventListener('click', () => {
                attendanceModal.classList.remove('active');
            });
            
            cancelAttendance.addEventListener('click', () => {
                attendanceModal.classList.remove('active');
            });
            
            // Tab Switching
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === attendanceModal) {
                    attendanceModal.classList.remove('active');
                }
            });
            
            // Chart Initialization
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            const attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    datasets: [{
                        label: 'Present',
                        data: [92, 94, 96, 93, 95, 90],
                        backgroundColor: '#10b981',
                        borderRadius: 5
                    }, {
                        label: 'Absent',
                        data: [8, 6, 4, 7, 5, 10],
                        backgroundColor: '#ef4444',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Weekly Attendance Trend'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
            
            // Add fade-in animation to table rows
            const tableRows = document.querySelectorAll('.attendance-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
            });
        }
    </script>
</body>
</html>