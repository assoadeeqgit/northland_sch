<?php
/**
 * Persistent Header Component
 * Expects $profile array and $pageTitle string to be defined.
 */
?>
<!-- Desktop Header -->
<header class="desktop-header bg-white shadow-md p-4 sticky top-0 z-20">
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <button id="mobileMenuToggle" class="md:hidden text-nsknavy mr-2">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-2xl font-bold text-nsknavy parameter-header-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
        </div>

        <div class="flex items-center space-x-4">
            <!-- Search Bar -->
            <div class="relative">
                <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4 border border-transparent focus-within:border-nskblue transition-colors">
                    <i class="fas fa-search text-gray-500"></i>
                    <input type="text" id="unifiedSearchInput" placeholder="Search students..." 
                           class="bg-transparent outline-none w-32 md:w-64 text-sm text-nsknavy">
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
                    <?= $profile['initials'] ?? 'T' ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-nsknavy">
                        <?= ($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '') ?>
                    </p>
                    <p class="text-xs text-gray-600"><?= $profile['specialization'] ?? '' ?> Teacher</p>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Header -->
<header class="mobile-header bg-white shadow-md p-4 sticky top-0 z-20 md:hidden">
    <div class="flex justify-between items-center w-full">
        <div class="flex items-center space-x-4">
            <button class="mobile-menu-toggle text-nsknavy mr-2" onclick="document.querySelector('.sidebar').classList.add('mobile-show'); document.getElementById('mobileOverlay').classList.add('active');">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-bold text-nsknavy parameter-header-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
        </div>

        <div class="flex items-center space-x-4">
            <div class="relative">
                <button id="notificationButtonMobile" class="relative">
                    <i class="fas fa-bell text-nsknavy text-xl"></i>
                    <div class="notification-dot"></div>
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                    <?= $profile['initials'] ?? 'T' ?>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Unified Search Logic
    const searchInput = document.getElementById('unifiedSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                if (window.location.href.includes('my_students.php')) {
                    // Handled by my_students.php scripts listening to this input?
                    // Or we trigger a custom event
                    const event = new Event('input', { bubbles: true });
                    searchInput.dispatchEvent(event);
                } else {
                    // Redirect
                    window.location.href = 'my_students.php?search=' + encodeURIComponent(e.target.value);
                }
            }
        });
    }
</script>
