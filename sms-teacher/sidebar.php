<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and get user data
$is_logged_in = isset($_SESSION['user_id']);
$user_type = $_SESSION['user_type'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Get teacher profile - fetch fresh data from database for consistency
if (!isset($profile)) {
    $profile = [
        'first_name' => 'N/A',
        'last_name' => 'N/A',
        'initials' => 'NN',
        'specialization' => 'N/A',
        'teacher_id' => 'N/A'
    ];

    if ($is_logged_in && $user_type === 'teacher') {
        try {
            require_once __DIR__ . '/config/database.php';
            $sidebar_database = new Database();
            $sidebar_db = $sidebar_database->getConnection();

            // Fetch fresh user and teacher data from database
            $sidebar_stmt = $sidebar_db->prepare("
                SELECT 
                    u.first_name, 
                    u.last_name,
                    t.teacher_id,
                    t.specialization,
                    tp.subject_specialization
                FROM users u
                LEFT JOIN teachers t ON u.id = t.user_id
                LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
                WHERE u.id = ? AND u.is_active = 1
            ");
            $sidebar_stmt->execute([$user_id]);
            $sidebar_data = $sidebar_stmt->fetch(PDO::FETCH_ASSOC);

            if ($sidebar_data) {
                $profile['first_name'] = $sidebar_data['first_name'] ?? 'Teacher';
                $profile['last_name'] = $sidebar_data['last_name'] ?? 'User';
                $profile['initials'] = strtoupper(
                    substr($profile['first_name'], 0, 1) .
                    substr($profile['last_name'], 0, 1)
                );
                // Use subject_specialization from teacher_profiles, fallback to specialization from teachers table
                $profile['specialization'] = $sidebar_data['subject_specialization'] ?: ($sidebar_data['specialization'] ?: 'Teacher');
                $profile['teacher_id'] = $sidebar_data['teacher_id'] ?? 'N/A';
            }
        } catch (Exception $e) {
            // Fallback to session data if database fetch fails
            error_log("Sidebar: Error fetching teacher data: " . $e->getMessage());
            $profile['first_name'] = $_SESSION['first_name'] ?? 'Teacher';
            $profile['last_name'] = $_SESSION['last_name'] ?? 'User';
            $profile['initials'] = strtoupper(
                substr($profile['first_name'], 0, 1) .
                substr($profile['last_name'], 0, 1)
            );
            $profile['specialization'] = $_SESSION['specialization'] ?? 'Teacher';
            $profile['teacher_id'] = $_SESSION['teacher_id'] ?? 'N/A';
        }
    }
}
?>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar Navigation -->
<aside class="sidebar bg-nsknavy text-white h-screen fixed top-0 left-0 flex flex-col w-[250px] overflow-hidden">
    <div class="p-6 flex-1 flex flex-col overflow-hidden">
        <!-- Logo Section -->
        <div class="logo-container rounded-lg p-4 mb-8">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center mr-3 overflow-hidden">
                    <img src="school_logo.png" alt="NSK Logo" class="object-cover w-full h-full">
                </div>
                <div>
                    <h2 class="text-lg font-bold leading-tight sidebar-text">Northland<br>Schools</h2>
                    <p class="text-xs opacity-75 mt-1 sidebar-text">Kano, Nigeria</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-2 flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Dashboard -->
            <a href="teacher_dashboard.php"
                class="nav-link sidebar-link flex items-center p-3 rounded-lg transition <?= basename($_SERVER['PHP_SELF']) == 'teacher_dashboard.php' ? 'bg-nskblue text-white shadow-md' : 'hover:bg-nskblue hover:text-white' ?>">
                <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                <span class="font-medium sidebar-text">Dashboard</span>
            </a>

            <!-- Students -->
            <a href="my_students.php"
                class="nav-link sidebar-link flex items-center p-3 rounded-lg transition <?= basename($_SERVER['PHP_SELF']) == 'my_students.php' ? 'bg-nskblue text-white shadow-md' : 'hover:bg-nskblue hover:text-white' ?>">
                <i class="fas fa-user-graduate mr-3 w-5 text-center"></i>
                <span class="font-medium sidebar-text">Students</span>
            </a>

            <!-- Attendance -->
            <a href="attendance.php"
                class="nav-link sidebar-link flex items-center p-3 rounded-lg transition <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-nskblue text-white shadow-md' : 'hover:bg-nskblue hover:text-white' ?>">
                <i class="fas fa-clipboard-check mr-3 w-5 text-center"></i>
                <span class="font-medium sidebar-text">Attendance</span>
            </a>

            <!-- View Results -->
            <a href="view_results.php"
                class="nav-link sidebar-link flex items-center p-3 rounded-lg transition <?= basename($_SERVER['PHP_SELF']) == 'view_results.php' ? 'bg-nskblue text-white shadow-md' : 'hover:bg-nskblue hover:text-white' ?>">
                <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
                <span class="font-medium sidebar-text">View Results</span>
            </a>

            <!-- Settings -->
            <a href="settings.php"
                class="nav-link sidebar-link flex items-center p-3 rounded-lg transition <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-nskblue text-white shadow-md' : 'hover:bg-nskblue hover:text-white' ?>">
                <i class="fas fa-cog mr-3 w-5 text-center"></i>
                <span class="font-medium sidebar-text">Settings</span>
            </a>
        </nav>
    </div>

    <!-- Bottom Profile Section -->
    <div class="p-4 border-t border-blue-800 bg-nsknavy">
        <div class="bg-blue-900 rounded-xl p-3 shadow-lg">
            <div class="flex items-center mb-3">
                <div class="w-10 h-10 rounded-full bg-white text-nskblue flex items-center justify-center font-bold text-sm shadow-sm">
                    <?= $profile['initials'] ?>
                </div>
                <div class="ml-3 overflow-hidden sidebar-text">
                    <p class="text-white text-sm font-semibold truncate"><?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></p>
                    <p class="text-blue-300 text-xs truncate"><?= htmlspecialchars($profile['specialization']) ?> Teacher</p>
                </div>
            </div>
            <button onclick="confirmLogout()"
                class="flex items-center justify-center w-full py-2 bg-white text-nskblue rounded-lg text-sm font-semibold hover:bg-gray-100 transition shadow-sm">
                <i class="fas fa-sign-out-alt mr-2"></i> <span class="sidebar-text">Logout</span>
            </button>
        </div>
    </div>
</aside>

<script>
    function confirmLogout() {
        // Check if SweetAlert2 is loaded
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Logout?',
                text: 'Are you sure you want to log out?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        } else {
            // Fallback to standard confirm if SweetAlert2 is not loaded
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '../logout.php';
            }
        }
    }

    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle'); // Internal Close/Arrow
        const collapsedHamburger = document.getElementById('collapsedHamburger'); // Internal Hamburger (Desktop Collapsed)
        const mobileMenuToggles = document.querySelectorAll('.mobile-menu-toggle, #mobileMenuToggle'); // External Header Buttons (Mobile)
        const mobileOverlay = document.getElementById('mobileOverlay');
        const body = document.body;

        // Function to toggle sidebar on Desktop (Collapse/Expand)
        function toggleSidebarDesktop() {
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }

            // Update body class
            if (sidebar.classList.contains('collapsed')) {
                body.classList.add('sidebar-closed');
            } else {
                body.classList.remove('sidebar-closed');
            }
        }

        // Function to toggle sidebar on Mobile (Show/Hide)
        function toggleSidebarMobile() {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('active');

            if (sidebar.classList.contains('mobile-show')) {
                body.classList.remove('sidebar-closed'); // Open
            } else {
                body.classList.add('sidebar-closed'); // Closed
            }
        }

        // Event Listeners

        // 1. Internal Close/Arrow Button
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                if (window.innerWidth > 768) {
                    toggleSidebarDesktop();
                } else {
                    toggleSidebarMobile();
                }
            });
        }

        // 2. Internal Collapsed Hamburger (Desktop only)
        if (collapsedHamburger) {
            collapsedHamburger.addEventListener('click', () => {
                if (window.innerWidth > 768) {
                    toggleSidebarDesktop(); // Expand
                }
            });
        }

        // 3. External Mobile Toggles (Header)
        mobileMenuToggles.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default if it's a link
                toggleSidebarMobile();
            });
        });

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', toggleSidebarMobile);
        }

        // Close sidebar when a link is clicked (Mobile)
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebarMobile();
                }
            });
        });

        // Responsive adjustments on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                // Reset mobile specific classes
                sidebar.classList.remove('mobile-show');
                mobileOverlay.classList.remove('active');

                // Ensure correct body state based on 'collapsed' class
                if (sidebar.classList.contains('collapsed')) {
                    body.classList.add('sidebar-closed');
                } else {
                    body.classList.remove('sidebar-closed');
                }
            } else {
                // Mobile default state
                // body.classList.add('sidebar-closed');
            }
        });

        // Initial Check
        if (window.innerWidth <= 768) {
            body.classList.add('sidebar-closed');
        }
    });
</script>
<script src="js/spa-navigation.js"></script>