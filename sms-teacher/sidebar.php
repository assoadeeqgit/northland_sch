<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and get user data
$is_logged_in = isset($_SESSION['user_id']);
$user_type = $_SESSION['user_type'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Get teacher profile if user is a teacher
if (!isset($profile)) {
    $profile = [
        'first_name' => 'N/A',
        'last_name' => 'N/A',
        'initials' => 'NN',
        'specialization' => 'N/A',
        'teacher_id' => 'N/A'
    ];

    if ($is_logged_in && $user_type === 'teacher') {
        // You'll need to include your database connection and fetch the actual teacher profile
        // For now, using session data or default values
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
?>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar Navigation -->
<aside class="sidebar bg-nsknavy text-white h-screen fixed top-0 left-0 z-10">
    <div class="p-5">
        <div class="flex items-center justify-between mb-10">
            <div class="flex items-center space-x-2 logo-group">
                <div class="logo-container w-10 h-10 rounded-full flex items-center justify-center bg-white font-bold p-1"
                    style="background-color: white;">
                    <img src="school_logo.png" alt="NSK Logo" class="w-full h-full object-cover rounded-full">
                </div>
                <h1 class="text-xl font-bold sidebar-text logo-text">NORTHLAND SCHOOLS</h1>
            </div>

            <!-- Collapsed Hamburger (Visible only when sidebar is collapsed on Desktop) -->
            <button id="collapsedHamburger" class="text-white hover:text-gray-300 transition hidden">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <nav class="space-y-2">
            <a href="teacher_dashboard.php"
                class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'teacher_dashboard.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-tachometer-alt mr-3"></i> <span class="sidebar-text">Dashboard</span>
            </a>
            <!-- <a href="my_classes.php" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'my_classes.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-chalkboard mr-3"></i> <span class="sidebar-text">My Classes</span>
            </a> -->
            <a href="my_students.php"
                class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'my_students.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-user-graduate mr-3"></i> <span class="sidebar-text">Students</span>
            </a>
            <!-- <a href="assignments.php" class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-tasks mr-3"></i> <span class="sidebar-text">Assignments</span>
            </a> -->
            <a href="attendance.php"
                class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-clipboard-check mr-3"></i> <span class="sidebar-text">Attendance</span>
            </a>
            <a href="results.php"
                class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'results.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-upload mr-3"></i> <span class="sidebar-text">Upload Results</span>
            </a>
            <a href="view_results.php"
                class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'view_results.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-chart-bar mr-3"></i> <span class="sidebar-text">View Results</span>
            </a>
            <a href="settings.php"
                class="nav-link sidebar-link block py-3 px-4 rounded-lg hover:bg-nskblue transition <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-nskblue active' : '' ?>">
                <i class="fas fa-cog mr-3"></i>
                <span class="sidebar-text">Settings</span>
            </a>
        </nav>
    </div>

    <div class="absolute bottom-0 w-full p-5">
        <div class="flex items-center space-x-3 bg-nskblue p-3 rounded-lg mb-3 user-profile-card">
            <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center flex-shrink-0">
                <span class="font-bold"><?= $profile['initials'] ?></span>
            </div>
            <div class="sidebar-text overflow-hidden">
                <p class="text-sm font-semibold truncate"><?= $profile['first_name'] . ' ' . $profile['last_name'] ?>
                </p>
                <p class="text-xs opacity-80 truncate"><?= $profile['specialization'] ?> Teacher</p>
                <p class="text-xs opacity-80">ID: <?= $profile['teacher_id'] ?></p>
            </div>
        </div>

        <!-- Logout Button -->
        <button onclick="confirmLogout()"
            class="w-full flex items-center justify-center space-x-2 bg-nskred hover:bg-red-700 text-white py-2 px-4 rounded-lg transition duration-200 ease-in-out transform hover:scale-105">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sidebar-text">Log Out</span>
        </button>
    </div>
</aside>

<script>
    function confirmLogout() {
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = '../logout.php';
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