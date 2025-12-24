<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Northland Schools Kano</title>
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
        
        .message-active {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
        
        .message-unread {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        
        .message-item {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .message-item:hover {
            background-color: #f8fafc;
        }
        
        .chat-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 8px;
            position: relative;
        }
        
        .chat-bubble.sent {
            background-color: #3b82f6;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .chat-bubble.received {
            background-color: #f1f5f9;
            color: #334155;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }
        
        .chat-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background-color: #f1f5f9;
            border-radius: 18px;
            margin-right: auto;
            max-width: fit-content;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: #94a3b8;
            border-radius: 50%;
            margin: 0 2px;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
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
                <!-- <a href="gradebook.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition">
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
                <!-- <a href="messages.html" class="nav-link sidebar-link block py-3 px-4 rounded-lg bg-nskblue transition active">
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
                    <h1 class="text-2xl font-bold text-nsknavy">Messages</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search messages..." class="bg-transparent outline-none w-32 md:w-64">
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
                    <h1 class="text-xl font-bold text-nsknavy">Messages</h1>
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

        <!-- Messages Content -->
        <div class="p-4 md:p-6">
            <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 md:mb-6 space-y-3 md:space-y-0">
                    <h2 class="text-lg md:text-xl font-bold text-nsknavy">Messages</h2>
                    <div class="flex space-x-3">
                        <button class="bg-nskblue text-white px-3 py-2 rounded-lg hover:bg-nsknavy transition text-sm">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <button id="newMessageBtn" class="bg-nskgreen text-white px-3 py-2 rounded-lg hover:bg-green-600 transition text-sm">
                            <i class="fas fa-plus mr-2"></i>New Message
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- Message List -->
                    <div class="lg:col-span-1 border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 p-4 border-b border-gray-200">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-search text-gray-500"></i>
                                <input type="text" placeholder="Search conversations..." class="bg-transparent outline-none w-full text-sm">
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            <!-- Message 1 - Active -->
                            <div class="message-item message-active p-4 cursor-pointer">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold">
                                            MI
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm">Mrs. Ibrahim</p>
                                            <p class="text-xs text-gray-600">Parent of Ahmed Ibrahim</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">2 hours ago</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate">Thank you for the update on Ahmed's progress in mathematics class...</p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <span class="bg-nskblue text-white px-2 py-1 rounded-full text-xs">Grade 10A</span>
                                    <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs">2 new</span>
                                </div>
                            </div>
                            
                            <!-- Message 2 -->
                            <div class="message-item p-4 cursor-pointer">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold">
                                            FO
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm">Mr. Okafor</p>
                                            <p class="text-xs text-gray-600">Parent of Fatima Okafor</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">1 day ago</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate">I wanted to discuss Fatima's upcoming mathematics project...</p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <span class="bg-nskgreen text-white px-2 py-1 rounded-full text-xs">Grade 10B</span>
                                </div>
                            </div>
                            
                            <!-- Message 3 - Unread -->
                            <div class="message-item message-unread p-4 cursor-pointer">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                            SA
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm">School Administration</p>
                                            <p class="text-xs text-gray-600">Principal's Office</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">2 days ago</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate">Reminder: Staff meeting this Friday at 3 PM in the conference room...</p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <span class="bg-gray-500 text-white px-2 py-1 rounded-full text-xs">All Staff</span>
                                    <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs">Important</span>
                                </div>
                            </div>

                            <!-- Message 4 -->
                            <div class="message-item p-4 cursor-pointer">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white font-bold">
                                            CY
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm">Mrs. Yusuf</p>
                                            <p class="text-xs text-gray-600">Parent of Chinedu Yusuf</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">3 days ago</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate">Following up on our discussion about Chinedu's performance...</p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <span class="bg-nskblue text-white px-2 py-1 rounded-full text-xs">Grade 10A</span>
                                </div>
                            </div>

                            <!-- Message 5 -->
                            <div class="message-item p-4 cursor-pointer">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center text-white font-bold">
                                            AA
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm">Mr. Abdullahi</p>
                                            <p class="text-xs text-gray-600">Parent of Amina Abdullahi</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">1 week ago</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate">Thank you for the extra help you've been giving Amina after class...</p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <span class="bg-nskgreen text-white px-2 py-1 rounded-full text-xs">Grade 10B</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Content -->
                    <div class="lg:col-span-2 border border-gray-200 rounded-lg overflow-hidden flex flex-col">
                        <div class="bg-gray-50 p-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold text-lg">
                                        MI
                                    </div>
                                    <div>
                                        <p class="font-semibold">Mrs. Ibrahim</p>
                                        <p class="text-sm text-gray-600">Parent of Ahmed Ibrahim (Grade 10A)</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                        <i class="fas fa-video"></i>
                                    </button>
                                    <button class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex-1 p-4 overflow-y-auto max-h-96 bg-gray-50">
                            <div class="space-y-4">
                                <!-- Received Message -->
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                        MI
                                    </div>
                                    <div class="chat-bubble received">
                                        <p class="text-sm">Good morning Mr. Adeyemi, I wanted to follow up on Ahmed's performance in mathematics. He mentioned struggling with the recent algebra concepts.</p>
                                        <p class="chat-time">10:15 AM</p>
                                    </div>
                                </div>
                                
                                <!-- Sent Message -->
                                <div class="flex items-start space-x-3 justify-end">
                                    <div class="chat-bubble sent">
                                        <p class="text-sm">Good morning Mrs. Ibrahim. Ahmed is a bright student but I've noticed he's been having difficulty with quadratic equations. I'd recommend some extra practice.</p>
                                        <p class="chat-time text-blue-100">10:20 AM</p>
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                                        JA
                                    </div>
                                </div>
                                
                                <!-- Received Message -->
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                        MI
                                    </div>
                                    <div class="chat-bubble received">
                                        <p class="text-sm">Thank you for letting me know. Are there any specific resources you'd recommend for quadratic equations practice?</p>
                                        <p class="chat-time">10:22 AM</p>
                                    </div>
                                </div>
                                
                                <!-- Sent Message -->
                                <div class="flex items-start space-x-3 justify-end">
                                    <div class="chat-bubble sent">
                                        <p class="text-sm">Yes, I can provide some additional worksheets and online resources. I'll also be available for extra help during lunch breaks on Tuesday and Thursday.</p>
                                        <p class="chat-time text-blue-100">10:25 AM</p>
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                                        JA
                                    </div>
                                </div>
                                
                                <!-- Received Message -->
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                        MI
                                    </div>
                                    <div class="chat-bubble received">
                                        <p class="text-sm">That would be wonderful! Ahmed will definitely attend the Thursday session. Thank you for your support.</p>
                                        <p class="chat-time">10:28 AM</p>
                                    </div>
                                </div>

                                <!-- Typing Indicator -->
                                <div class="typing-indicator">
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <span class="text-sm text-gray-500 ml-2">Mrs. Ibrahim is typing...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 p-4">
                            <div class="flex space-x-2">
                                <button class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <button class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100">
                                    <i class="fas fa-image"></i>
                                </button>
                                <input type="text" placeholder="Type your message..." class="flex-1 border rounded-full px-4 py-2 text-sm outline-none focus:border-nskblue">
                                <button class="bg-nskblue text-white p-2 rounded-full hover:bg-nsknavy transition">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <!-- Broadcast Message -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                    <h3 class="text-lg font-bold text-nsknavy mb-4">Broadcast Message</h3>
                    <div class="space-y-3">
                        <button class="w-full p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition text-left">
                            <i class="fas fa-users text-nskblue mr-2"></i>
                            <span class="font-semibold text-nskblue">All Parents</span>
                        </button>
                        <button class="w-full p-3 bg-green-50 rounded-lg hover:bg-green-100 transition text-left">
                            <i class="fas fa-user-graduate text-nskgreen mr-2"></i>
                            <span class="font-semibold text-nskgreen">All Students</span>
                        </button>
                        <button class="w-full p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition text-left">
                            <i class="fas fa-chalkboard text-nskgold mr-2"></i>
                            <span class="font-semibold text-nskgold">Specific Class</span>
                        </button>
                    </div>
                </div>

                <!-- Message Templates -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                    <h3 class="text-lg font-bold text-nsknavy mb-4">Quick Templates</h3>
                    <div class="space-y-3">
                        <button class="w-full p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left text-sm">
                            <i class="fas fa-clock text-gray-600 mr-2"></i>
                            <span>Assignment reminder</span>
                        </button>
                        <button class="w-full p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left text-sm">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>
                            <span>Low grade alert</span>
                        </button>
                        <button class="w-full p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left text-sm">
                            <i class="fas fa-trophy text-green-500 mr-2"></i>
                            <span>Excellent work</span>
                        </button>
                        <button class="w-full p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition text-left text-sm">
                            <i class="fas fa-calendar text-blue-500 mr-2"></i>
                            <span>Parent meeting reminder</span>
                        </button>
                    </div>
                </div>

                <!-- Message Statistics -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                    <h3 class="text-lg font-bold text-nsknavy mb-4">Message Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Messages</span>
                            <span class="font-semibold">147</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Unread</span>
                            <span class="font-semibold text-nskred">8</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">This Week</span>
                            <span class="font-semibold text-nskgreen">23</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Response Rate</span>
                            <span class="font-semibold text-nskgreen">94%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Action Button for Mobile -->
    <button class="floating-btn md:hidden bg-nskblue text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center">
        <i class="fas fa-plus text-xl"></i>
    </button>

    <!-- New Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content w-full max-w-md md:max-w-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy">New Message</h3>
                <button id="closeMessageModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipient</label>
                    <select class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                        <option>Select recipient...</option>
                        <option>All Students - Grade 10A</option>
                        <option>All Students - Grade 10B</option>
                        <option>All Students - Grade 11A</option>
                        <option>All Parents - Grade 10A</option>
                        <option>All Parents - Grade 10B</option>
                        <option>School Administration</option>
                        <option>Individual Student/Parent</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="Enter message subject">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea rows="5" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="Type your message here..."></textarea>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="urgentMessage" class="rounded border-gray-300">
                    <label for="urgentMessage" class="text-sm text-gray-700">Mark as urgent</label>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="requestReply" class="rounded border-gray-300" checked>
                    <label for="requestReply" class="text-sm text-gray-700">Request reply</label>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancelMessage" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-nskgreen text-white rounded-lg text-sm hover:bg-green-600 transition">Send Message</button>
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
        const newMessageBtn = document.getElementById('newMessageBtn');
        const messageModal = document.getElementById('messageModal');
        const closeMessageModal = document.getElementById('closeMessageModal');
        const cancelMessage = document.getElementById('cancelMessage');
        const messageItems = document.querySelectorAll('.message-item');
        const chatContainer = document.querySelector('.flex-1.overflow-y-auto');

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

        // Select message conversation
        function handleMessageSelect(e) {
            // Remove active class from all messages
            messageItems.forEach(item => {
                item.classList.remove('message-active');
                item.classList.remove('bg-blue-50');
            });
            
            // Add active class to selected message
            const messageItem = e.currentTarget;
            messageItem.classList.add('message-active');
            messageItem.classList.add('bg-blue-50');
            
            // Remove unread indicator
            messageItem.classList.remove('message-unread');
            
            // In a real app, this would load the conversation
            console.log('Loading conversation...');
        }

        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        // Simulate typing indicator
        function simulateTyping() {
            const typingIndicator = document.querySelector('.typing-indicator');
            if (typingIndicator) {
                setTimeout(() => {
                    // In a real app, this would be replaced with an actual message
                    typingIndicator.style.display = 'none';
                }, 2000);
            }
        }

        // Send message
        function handleSendMessage() {
            const messageInput = document.querySelector('input[placeholder="Type your message..."]');
            const message = messageInput.value.trim();
            
            if (message) {
                // In a real app, this would send the message to the server
                console.log('Sending message:', message);
                
                // Clear input
                messageInput.value = '';
                
                // Show success notification
                showNotification('Message sent successfully!', 'success');
            }
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

        // Event Listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);

        // Message items
        messageItems.forEach(item => {
            item.addEventListener('click', handleMessageSelect);
        });

        // New message modal
        newMessageBtn.addEventListener('click', () => openModal(messageModal));
        closeMessageModal.addEventListener('click', () => closeModalFunc(messageModal));
        cancelMessage.addEventListener('click', () => closeModalFunc(messageModal));

        // Send message on enter key
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && e.target.placeholder === 'Type your message...') {
                handleSendMessage();
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === messageModal) {
                closeModalFunc(messageModal);
            }
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', () => {
            scrollToBottom();
            simulateTyping();
            console.log('Messages page loaded successfully');
        });

        // Responsive adjustments
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-show');
                mobileOverlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>