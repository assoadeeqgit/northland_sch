<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management - Northland Schools Kano</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
        
        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        
        .finance-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .finance-card:hover {
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
        
        .finance-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .finance-table th {
            background-color: #f8fafc;
        }
        
        .finance-table tr:last-child td {
            border-bottom: 0;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: scale(0.9);
            opacity: 0;
            display: none;
        }
        
        .modal.active {
            transform: scale(1);
            opacity: 1;
            display: flex;
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
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Chart container styles */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
    </style>
</head>
<body class="flex">
    <!-- Sidebar Navigation -->
    <?php require_once 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Finance Management</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" placeholder="Search transactions..." class="bg-transparent outline-none w-32 md:w-64">
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

        <!-- Finance Management Content -->
        <div class="p-6">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="finance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-money-bill-wave text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-nsknavy">₦8.2M</p>
                        <p class="text-xs text-nskgreen"><i class="fas fa-arrow-up"></i> 12% from last month</p>
                    </div>
                </div>
                
                <div class="finance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskblue p-4 rounded-full mr-4">
                        <i class="fas fa-receipt text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Fee Collection Rate</p>
                        <p class="text-2xl font-bold text-nsknavy">78%</p>
                        <p class="text-xs text-gray-600">₦2.1M pending</p>
                    </div>
                </div>
                
                <div class="finance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-credit-card text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Expenses</p>
                        <p class="text-2xl font-bold text-nsknavy">₦4.7M</p>
                        <p class="text-xs text-nskred">+8% from last month</p>
                    </div>
                </div>
                
                <div class="finance-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Overdue Payments</p>
                        <p class="text-2xl font-bold text-nsknavy">42</p>
                        <p class="text-xs text-nskred">₦1.3M overdue</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="finance-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Revenue vs Expenses</h2>
                    <div class="chart-container">
                        <canvas id="revenueExpenseChart"></canvas>
                    </div>
                </div>
                
                <div class="finance-card bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-nsknavy mb-4">Fee Collection Progress</h2>
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Term 1 Fees</span>
                                <span class="font-semibold">92%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskgreen" style="width: 92%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">₦3.2M of ₦3.5M</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Term 2 Fees</span>
                                <span class="font-semibold">78%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskblue" style="width: 78%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">₦2.7M of ₦3.5M</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Term 3 Fees</span>
                                <span class="font-semibold">65%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskgold" style="width: 65%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">₦2.3M of ₦3.5M</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-nsknavy">Financial Transactions</h2>
                    
                    <div class="flex flex-wrap gap-4">
                        <select class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">All Transactions</option>
                            <option value="fee">Fee Payments</option>
                            <option value="expense">Expenses</option>
                            <option value="other">Other Income</option>
                        </select>
                        
                        <select class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                        
                        <button class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        
                        <button id="recordPaymentBtn" class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Record Payment
                        </button>
                        
                        <button class="bg-nskgold text-white px-4 py-2 rounded-lg font-semibold hover:bg-amber-600 transition flex items-center">
                            <i class="fas fa-file-export mr-2"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full finance-table">
                        <thead>
                            <tr>
                                <th class="py-3 px-6 text-left text-nsknavy">Transaction</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Student</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Date</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Amount</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Status</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="py-4 px-6">
                                    <div>
                                        <p class="font-semibold">Term 2 Tuition Fee</p>
                                        <p class="text-sm text-gray-600">ID: TXN-2023-1056</p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Ahmad Abdullahi</p>
                                    <p class="text-sm text-gray-600">Grade 10-B</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">15 Nov 2023</p>
                                    <p class="text-xs text-gray-600">10:45 AM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-nskgreen">₦85,000</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-green-100 text-nskgreen">Paid</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class="py-4 px-6">
                                    <div>
                                        <p class="font-semibold">Term 3 Tuition Fee</p>
                                        <p class="text-sm text-gray-600">ID: TXN-2023-1089</p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Fatima Mohammed</p>
                                    <p class="text-sm text-gray-600">Grade 9-A</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">Due: 30 Nov 2023</p>
                                    <p class="text-xs text-gray-600">5 days remaining</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-nskgold">₦85,000</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-yellow-100 text-yellow-700">Pending</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class="py-4 px-6">
                                    <div>
                                        <p class="font-semibold">Term 1 Tuition Fee</p>
                                        <p class="text-sm text-gray-600">ID: TXN-2023-0923</p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Yusuf Ibrahim</p>
                                    <p class="text-sm text-gray-600">Grade 11-C</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">Due: 15 Oct 2023</p>
                                    <p class="text-xs text-nskred">Overdue by 20 days</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-nskred">₦85,000</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-red-100 text-nskred">Overdue</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class="py-4 px-6">
                                    <div>
                                        <p class="font-semibold">Library Fine</p>
                                        <p class="text-sm text-gray-600">ID: TXN-2023-1102</p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Hauwa Abubakar</p>
                                    <p class="text-sm text-gray-600">Grade 8-B</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">12 Nov 2023</p>
                                    <p class="text-xs text-gray-600">02:15 PM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-nskgreen">₦2,500</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-green-100 text-nskgreen">Paid</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td class="py-4 px-6">
                                    <div>
                                        <p class="font-semibold">School Trip Payment</p>
                                        <p class="text-sm text-gray-600">ID: TXN-2023-1098</p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-medium">Zainab Mohammed</p>
                                    <p class="text-sm text-gray-600">Grade 12-A</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-sm">10 Nov 2023</p>
                                    <p class="text-xs text-gray-600">09:30 AM</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-semibold text-nskgreen">₦15,000</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="status-badge bg-green-100 text-nskgreen">Paid</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-2">
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">128</span> transactions
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 rounded border text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Previous
                        </button>
                        <button class="px-3 py-1 rounded border border-nskblue bg-nskblue text-white text-sm font-medium">
                            1
                        </button>
                        <button class="px-3 py-1 rounded border text-sm font-medium text-gray-700 hover:bg-gray-50">
                            2
                        </button>
                        <button class="px-3 py-1 rounded border text-sm font-medium text-gray-700 hover:bg-gray-50">
                            3
                        </button>
                        <button class="px-3 py-1 rounded border text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <!-- Expense Overview -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-nsknavy mb-6">Expense Breakdown</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="chart-container">
                        <canvas id="expenseChart"></canvas>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Staff Salaries</span>
                                <span class="font-semibold">₦2.8M</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskblue" style="width: 60%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">60% of total expenses</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Facilities Maintenance</span>
                                <span class="font-semibold">₦750,000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskgreen" style="width: 16%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">16% of total expenses</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Teaching Materials</span>
                                <span class="font-semibold">₦450,000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskgold" style="width: 10%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">10% of total expenses</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Utilities</span>
                                <span class="font-semibold">₦400,000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-amber-600" style="width: 8%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">8% of total expenses</p>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Other Expenses</span>
                                <span class="font-semibold">₦300,000</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill bg-nskred" style="width: 6%"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">6% of total expenses</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div id="recordPaymentModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Record Payment</h3>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="paymentForm" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2" for="student">Student</label>
                        <select id="student" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="">Select Student</option>
                            <option value="ahmad">Ahmad Abdullahi (Grade 10-B)</option>
                            <option value="fatima">Fatima Mohammed (Grade 9-A)</option>
                            <option value="yusuf">Yusuf Ibrahim (Grade 11-C)</option>
                            <option value="hauwa">Hauwa Abubakar (Grade 8-B)</option>
                            <option value="zainab">Zainab Mohammed (Grade 12-A)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2" for="paymentType">Payment Type</label>
                        <select id="paymentType" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="">Select Payment Type</option>
                            <option value="tuition">Tuition Fee</option>
                            <option value="library">Library Fine</option>
                            <option value="trip">School Trip</option>
                            <option value="uniform">Uniform</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2" for="term">Term</label>
                        <select id="term" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="">Select Term</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2" for="amount">Amount (₦)</label>
                        <input type="number" id="amount" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" placeholder="Enter amount" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2" for="paymentDate">Payment Date</label>
                        <input type="date" id="paymentDate" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2" for="paymentMethod">Payment Method</label>
                        <select id="paymentMethod" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="card">Card Payment</option>
                            <option value="mobile">Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" id="cancelBtn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Include footer -->
        <script src="footer.js"></script>
    </main>
<script>
    // Wait for everything to load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing finance page...');
        
        // Initialize charts
        setTimeout(function() {
            initializeCharts();
        }, 300);

        // Initialize all finance functionality
        initializeFinancePage();
    });

    function initializeCharts() {
        // Revenue vs Expense Chart
        const revenueExpenseCanvas = document.getElementById('revenueExpenseChart');
        if (revenueExpenseCanvas) {
            try {
                const revenueExpenseCtx = revenueExpenseCanvas.getContext('2d');
                new Chart(revenueExpenseCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'],
                        datasets: [
                            {
                                label: 'Revenue',
                                data: [65, 59, 80, 81, 56, 55, 72, 78, 80, 85, 82],
                                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                borderColor: 'rgb(16, 185, 129)',
                                borderWidth: 1
                            },
                            {
                                label: 'Expenses',
                                data: [28, 48, 40, 19, 36, 27, 35, 42, 46, 48, 47],
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₦' + value + 'K';
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                console.log('Revenue vs Expense chart initialized successfully');
            } catch (error) {
                console.error('Error initializing revenue vs expense chart:', error);
            }
        }

        // Expense Breakdown Chart
        const expenseCanvas = document.getElementById('expenseChart');
        if (expenseCanvas) {
            try {
                const expenseCtx = expenseCanvas.getContext('2d');
                new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Staff Salaries', 'Facilities Maintenance', 'Teaching Materials', 'Utilities', 'Other Expenses'],
                        datasets: [{
                            data: [60, 16, 10, 8, 6],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(217, 119, 6, 0.8)',
                                'rgba(239, 68, 68, 0.8)'
                            ],
                            borderColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)',
                                'rgb(217, 119, 6)',
                                'rgb(239, 68, 68)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + '%';
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Expense breakdown chart initialized successfully');
            } catch (error) {
                console.error('Error initializing expense breakdown chart:', error);
            }
        }
    }

    function initializeFinancePage() {
        console.log('Initializing finance functionality...');
        
        // Initialize all components
        initializeFilters();
        initializeActionButtons();
        initializeTableActions();
        initializePagination();
        initializeModal();
        initializeSearch();
        initializeNotifications();
        initializeExport();
        
        console.log('Finance functionality initialized');
    }

    function initializeFilters() {
        const filterBtn = document.querySelector('button:has(i.fa-filter)');
        if (filterBtn) {
            filterBtn.addEventListener('click', function() {
                const transactionTypeSelect = document.querySelector('select:first-of-type');
                const statusSelect = document.querySelector('select:last-of-type');
                
                const transactionType = transactionTypeSelect ? transactionTypeSelect.value : '';
                const status = statusSelect ? statusSelect.value : '';
                
                showNotification(`Filters applied: ${transactionType || 'All Types'}, ${status || 'All Status'}`, 'success');
            });
        }
    }

    function initializeActionButtons() {
        // Export Button
        const exportBtn = document.querySelector('button:has(i.fa-file-export)');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                showNotification('Preparing export file...', 'info');
                
                setTimeout(() => {
                    showNotification('Finance data exported successfully!', 'success');
                }, 1500);
            });
        }
    }

    function initializeTableActions() {
        // Table action buttons (receipt and edit)
        document.querySelectorAll('.finance-table button').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const transactionId = row.querySelector('td:first-child .text-sm').textContent.replace('ID: ', '');
                
                if (this.innerHTML.includes('fa-receipt')) {
                    showNotification(`Generating receipt for: ${transactionId}`, 'info');
                } else if (this.innerHTML.includes('fa-edit')) {
                    showNotification(`Editing transaction: ${transactionId}`, 'info');
                }
            });
        });
    }

    function initializePagination() {
        const paginationButtons = document.querySelectorAll('.flex.space-x-2 button');
        
        paginationButtons.forEach(button => {
            button.addEventListener('click', function() {
                const buttonText = this.textContent.trim();
                
                if (buttonText === 'Previous' || buttonText === 'Next') {
                    showNotification(`${buttonText} page clicked`, 'info');
                } else if (!isNaN(buttonText)) {
                    showNotification(`Navigated to page ${buttonText}`, 'info');
                }
            });
        });
    }

    function initializeModal() {
        const modal = document.getElementById('recordPaymentModal');
        const recordPaymentBtn = document.getElementById('recordPaymentBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const paymentForm = document.getElementById('paymentForm');

        // Record Payment Button
        if (recordPaymentBtn) {
            recordPaymentBtn.addEventListener('click', function() {
                if (modal) {
                    modal.classList.add('active');
                }
            });
        }

        // Close Modal Functions
        function closeModalFunc() {
            if (modal) {
                modal.classList.remove('active');
            }
        }

        if (closeModal) {
            closeModal.addEventListener('click', closeModalFunc);
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModalFunc);
        }

        // Close modal when clicking outside
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModalFunc();
                }
            });
        }

        // Form submission
        if (paymentForm) {
            paymentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const studentSelect = document.getElementById('student');
                const amountInput = document.getElementById('amount');
                
                const student = studentSelect ? studentSelect.options[studentSelect.selectedIndex].text : '';
                const amount = amountInput ? amountInput.value : '';
                
                if (!student || !amount) {
                    showNotification('Please fill in all required fields', 'error');
                    return;
                }
                
                showNotification(`Payment of ₦${amount} recorded for ${student}`, 'success');
                closeModalFunc();
                paymentForm.reset();
            });
        }
        
        // Set default date to today
        const paymentDateInput = document.getElementById('paymentDate');
        if (paymentDateInput) {
            const today = new Date().toISOString().split('T')[0];
            paymentDateInput.value = today;
        }
    }

    function initializeSearch() {
        const searchInput = document.querySelector('input[placeholder*="Search transactions"]');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                if (e.target.value.length > 2) {
                    showNotification(`Searching for: ${e.target.value}`, 'info');
                }
            });
        }
    }

    function initializeNotifications() {
        const notificationBell = document.querySelector('.fa-bell');
        if (notificationBell) {
            const notificationParent = notificationBell.closest('.relative');
            if (notificationParent) {
                notificationParent.addEventListener('click', function() {
                    showNotification('You have 3 new notifications', 'info');
                });
            }
        }
    }

    function initializeExport() {
        const exportBtn = document.querySelector('button:has(i.fa-file-export)');
        if (exportBtn) {
            exportBtn.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showNotification('Right-click for export options', 'info');
            });
        }
    }

    // Notification function
    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.custom-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create new notification
        const notification = document.createElement('div');
        notification.className = `custom-notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-transform duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                    type === 'warning' ? 'fa-exclamation-triangle' :
                    'fa-info-circle'
                } mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Add CSS for notifications
    const style = document.createElement('style');
    style.textContent = `
        .custom-notification {
            transform: translateX(100%);
        }
    `;
    document.head.appendChild(style);
</script>
</html>