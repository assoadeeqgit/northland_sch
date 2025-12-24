<?php
/**
 * Header Component
 * Displays the top navigation bar with user info and notifications
 * 
 * Required variables from including page:
 * - $pageTitle (optional): The title to display in the header
 */

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user ID from session
$header_user_id = $_SESSION['user_id'] ?? null;
$header_user_type = $_SESSION['user_type'] ?? 'guest';

// Fetch fresh user data from database
$header_user_name = 'Guest User';
$header_user_role = 'Guest';
$header_user_initials = 'GU';

if ($header_user_id) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $header_database = new Database();
        $header_db = $header_database->getConnection();

        // Fetch user details from database
        $header_stmt = $header_db->prepare("
            SELECT first_name, last_name, user_type, email 
            FROM users 
            WHERE id = ? AND is_active = 1
        ");
        $header_stmt->execute([$header_user_id]);
        $header_user_data = $header_stmt->fetch(PDO::FETCH_ASSOC);

        if ($header_user_data) {
            // Set user name
            $header_user_name = trim($header_user_data['first_name'] . ' ' . $header_user_data['last_name']);

            // Set user role (capitalize first letter)
            $header_user_role = ucfirst($header_user_data['user_type']);

            // Create initials
            $header_name_parts = explode(' ', $header_user_name, 2);
            $header_first_initial = $header_name_parts[0][0] ?? 'G';
            $header_last_initial = isset($header_name_parts[1]) ? ($header_name_parts[1][0] ?? '') : '';
            $header_user_initials = strtoupper($header_first_initial . $header_last_initial);
        }
    } catch (Exception $e) {
        // Silently fail and use session data as fallback
        error_log("Header: Error fetching user data: " . $e->getMessage());

        // Fallback to session data
        if (isset($_SESSION['user_name'])) {
            $header_user_name = $_SESSION['user_name'];
            $header_name_parts = explode(' ', $header_user_name, 2);
            $header_first_initial = $header_name_parts[0][0] ?? 'G';
            $header_last_initial = isset($header_name_parts[1]) ? ($header_name_parts[1][0] ?? '') : '';
            $header_user_initials = strtoupper($header_first_initial . $header_last_initial);
        }
        if (isset($_SESSION['user_type'])) {
            $header_user_role = ucfirst($_SESSION['user_type']);
        }
    }
}

// Default page title if not set
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? ''; // Optional subtitle
?>

<header class="bg-white shadow-md p-4">
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <button class="sidebar-toggle md:hidden text-nsknavy">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div>
                <h1 class="text-2xl font-bold text-nsknavy"><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if (!empty($pageSubtitle)): ?>
                    <p class="text-gray-600"><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <div class="relative">
                <i class="fas fa-bell text-nsknavy text-xl"></i>
                <div class="notification-dot"></div>
            </div>

            <div class="hidden md:flex items-center space-x-2">
                <div class="w-10 h-10 rounded-full bg-nskblue flex items-center justify-center text-white font-bold">
                    <?= htmlspecialchars($header_user_initials) ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-nsknavy"><?= htmlspecialchars($header_user_name) ?></p>
                    <p class="text-xs text-gray-600"><?= htmlspecialchars($header_user_role) ?></p>
                </div>
            </div>
        </div>
    </div>
</header>