<!-- This file does all the work of loading the sidebar with the correct user info. -->
<!-- Start session if it's not already started -->
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user details from session for the sidebar
$sidebar_user_name = $_SESSION['user_name'] ?? 'Guest User';
$sidebar_user_role = ucfirst($_SESSION['user_type'] ?? 'Guest'); // 'admin' -> 'Guest'

// Create initials for the sidebar
$sidebar_name_parts = explode(' ', $sidebar_user_name, 2);
$sidebar_first_initial = $sidebar_name_parts[0][0] ?? 'G';
$sidebar_last_initial = isset($sidebar_name_parts[1]) ? ($sidebar_name_parts[1][0] ?? '') : '';
$sidebar_user_initials = strtoupper($sidebar_first_initial . $sidebar_last_initial);

// Get the current page file name
$current_page = basename($_SERVER['PHP_SELF']);

// Get session token for logout
$session_token = $_SESSION['session_token'] ?? '';
?>

<!-- Sidebar Navigation -->
<aside class="sidebar bg-nsknavy text-white h-screen fixed top-0 left-0 z-10">
    <div class="p-6 h-full flex flex-col">
        <div class="logo-container rounded-lg p-4 mb-8">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center mr-3">
                    <!-- This path assumes the logo is in the root folder -->
                    <img src="../school_logo.png" alt="logo">
                </div>
                <div>
                    <h2 class="text-lg font-bold">Northland Schools</h2>
                    <p class="text-xs opacity-75">Kano, Nigeria</p>
                </div>
            </div>
        </div>

        <nav class="space-y-2 flex-1 overflow-y-auto" id="sidebar-nav">
            <!-- Navigation items will be populated dynamically by JS below -->
        </nav>
    </div>

    <div class="absolute bottom-0 left-0 right-0 p-6">
        <div class="bg-nskblue rounded-lg p-4">
            <div class="flex items-center mb-2">
                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center mr-3">
                    <!-- DYNAMIC INITIALS -->
                    <span class="text-nskblue font-bold text-sm"><?= $sidebar_user_initials ?></span>
                </div>
                <div>
                    <!-- DYNAMIC NAME & ROLE -->
                    <p class="font-semibold text-sm"><?= htmlspecialchars($sidebar_user_name) ?></p>
                    <p class="text-xs opacity-75"><?= htmlspecialchars($sidebar_user_role) ?></p>
                </div>
            </div>
            <a href="../logout.php<?= $session_token ? '?token=' . urlencode($session_token) : '' ?>"
                class="w-full bg-white text-nskblue py-2 px-4 rounded-lg text-sm font-semibold hover:bg-gray-100 transition flex items-center justify-center logout-link">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </div>
</aside>

<!-- This <script> tag contains all the logic from your old sidebar.js file -->
<script>
    // sidebar.js - Reusable sidebar component
    class SidebarManager {
        constructor() {
            this.navigationItems = [{
                    href: 'admin-dashboard.php',
                    icon: 'fas fa-tachometer-alt',
                    text: 'Dashboard'
                },
                {
                    href: 'students-management.php',
                    icon: 'fas fa-user-graduate',
                    text: 'Students'
                },
                {
                    href: 'teachers-management.php',
                    icon: 'fas fa-chalkboard-teacher',
                    text: 'Teachers'
                },
                {
                    href: 'classes.php',
                    icon: 'fas fa-school',
                    text: 'Classes'
                },
                {
                    href: 'academics-management.php',
                    icon: 'fas fa-book',
                    text: 'Academics'
                },
                {
                    href: 'timetable-management.php',
                    icon: 'fas fa-clock',
                    text: 'Timetable'
                },
                {
                    href: 'term-management.php',
                    icon: 'fas fa-calendar-alt',
                    text: 'Term Management'
                },
                {
                    href: '../accountant-dashboard/index.php',
                    icon: 'fas fa-dollar-sign',
                    text: 'Finance'
                },
                {
                    href: 'report.php',
                    icon: 'fas fa-chart-bar',
                    text: 'Reports'
                },
                {
                    href: 'user-management.php',
                    icon: 'fas fa-users',
                    text: 'Users'
                },
                {
                    href: 'settings.php',
                    icon: 'fas fa-cog',
                    text: 'Settings'
                }
            ];

            // Bind methods to maintain 'this' context
            this.toggleSidebar = this.toggleSidebar.bind(this);
            this.hideSidebar = this.hideSidebar.bind(this);
            this.handleLogout = this.handleLogout.bind(this);
            this.handleOutsideClick = this.handleOutsideClick.bind(this);
        }

        // Initialize sidebar
        init(currentPage = '') {
            this.populateNavigation(currentPage);
            this.setupEventListeners();
        }

        // Populate navigation items
        populateNavigation(currentPage) {
            const navContainer = document.getElementById('sidebar-nav');
            if (!navContainer) return;

            navContainer.innerHTML = this.navigationItems.map(item => {

                // Handle 'report.php' alias
                if (item.href === 'report.php') item.href = 'report.php';
                if (currentPage === 'report.php') currentPage = 'report.php';

                const isActive = currentPage === item.href;
                const activeClass = isActive ? 'bg-nskblue text-white' : 'hover:bg-nskblue hover:text-white';

                return `
                <a href="${item.href}" class="flex items-center p-3 rounded-lg ${activeClass} transition nav-item">
                    <i class="${item.icon} mr-3"></i>
                    <span>${item.text}</span>
                </a>
            `;
            }).join('');
        }

        // Setup event listeners
        setupEventListeners() {
            // This runs *after* DOMContentLoaded, so we query directly.
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', this.toggleSidebar);
            }

            const logoutLink = document.querySelector('.logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', this.handleLogout);
            }

            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        this.hideSidebar();
                    }
                });
            });

            document.addEventListener('click', this.handleOutsideClick);
        }

        // Handle outside clicks to close sidebar
        handleOutsideClick(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');

            if (window.innerWidth < 768 &&
                sidebar &&
                sidebar.classList.contains('mobile-show') &&
                !sidebar.contains(event.target) &&
                (!sidebarToggle || !sidebarToggle.contains(event.target))) {
                this.hideSidebar();
            }
        }

        // Toggle sidebar visibility (mobile)
        toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-show');
            }
        }

        // Hide sidebar (mobile)
        hideSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.remove('mobile-show');
            }
        }

        // Handle logout with confirmation
        handleLogout(event) {
            if (!confirm('Are you sure you want to logout?')) {
                event.preventDefault();
            }
            // If confirmed, the link will proceed naturally
        }
    }

    // Create global instance
    window.sidebarManager = new SidebarManager();

    // Auto-initialize if script is loaded
    // We get the current page name from PHP
    const currentPage = '<?= $current_page ?>';
    window.sidebarManager.init(currentPage);
</script>