<?php 
require_once __DIR__ . '/../config/config.php';

// Initials Logic (User Profile)
$user_name = $_SESSION['user_name'] ?? 'Accountant';
$user_role = 'Accountant'; // Fixed for this dashboard
$name_parts = explode(' ', $user_name, 2);
$first_initial = $name_parts[0][0] ?? 'A';
$last_initial = isset($name_parts[1]) ? ($name_parts[1][0] ?? '') : '';
$user_initials = strtoupper($first_initial . $last_initial);

// Current Page for Active State
$current_page = basename($_SERVER['PHP_SELF']);

// --- FETCH CURRENT TERM & SESSION ---
$current_term_display = 'Session: N/A';
try {
    require_once __DIR__ . '/../config/DatabaseManager.php';
    $dbManager = DatabaseManager::getInstance();
    $conn_shared = $dbManager->getConnection();
    
    $stmt_term = $conn_shared->query("
        SELECT t.term_name, s.session_name 
        FROM terms t 
        JOIN academic_sessions s ON t.session_id = s.id 
        WHERE t.is_current = 1 LIMIT 1
    ");
    $term_info = $stmt_term->fetch(PDO::FETCH_ASSOC);
    
    if ($term_info) {
        $current_term_display = htmlspecialchars($term_info['session_name'] . ' - ' . $term_info['term_name']);
    }
} catch (Exception $e) {
    // Fail silently, keep default
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northland Schools Kano - Financial System</title>
    
    <!-- Tailwind CSS (Pre-compiled for performance) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tailwind.min.css?v=<?php echo time(); ?>">

    <!-- CSS (Legacy/Global) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper" style="display: flex; min-height: 100vh;">
        <!-- Sidebar -->
        <aside class="sidebar bg-nsknavy text-white h-screen fixed top-0 left-0 z-50 w-64 transition-transform duration-300 ease-in-out" 
               style="width: 256px; background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%); position: fixed; height: 100vh; z-index: 50;">
            <div class="p-6 h-full flex flex-col" style="padding: 1.5rem; display: flex; flex-direction: column; height: 100%;">
                <!-- Logo Section (Matches Admin 'logo-container') -->
                <div class="logo-container rounded-lg p-4 mb-6" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.5rem;">
                    <div class="flex items-center" style="display: flex; align-items: center;">
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center mr-3 flex-shrink-0" style="width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem; flex-shrink: 0;">
                            <img src="<?php echo BASE_URL; ?>/assets/images/logo.jpeg" alt="NSK Logo" class="object-cover w-full h-full rounded-full" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        </div>
                        <div>
                            <h2 class="text-lg font-bold leading-tight" style="font-size: 1.125rem; font-weight: 700; line-height: 1.25;">Northland<br>Schools</h2>
                            <p class="text-xs opacity-75 mt-1" style="font-size: 0.75rem; opacity: 0.75; margin-top: 0.25rem;">Kano, Nigeria</p>
                        </div>
                    </div>
                </div>
                
                <?php 
                $dash_prefix = ($_SESSION['user_type'] ?? '') == 'accountant' ? '/accountant-dashboard' : '';
                ?>
                
                <!-- Navigation -->
                <!-- Added pb-40 to ensure content isn't hidden behind absolute profile footer -->
                <nav class="space-y-1 flex-1 overflow-y-auto pb-48" id="sidebar-nav" style="flex: 1; overflow-y: auto; padding-bottom: 12rem;">
                    <!-- Dashboard -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/index.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'index.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'index.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-chart-line mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Payments -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/payment.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'payment.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'payment.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-credit-card mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Payments</span>
                    </a>

                    <!-- Fees -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/fees.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'fees.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'fees.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-file-invoice-dollar mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Fees</span>
                    </a>

                    <!-- Students (Fixed Icon & Spacing) -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/students.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'students.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'students.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-user-graduate mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Students</span>
                    </a>

                    <!-- Expenses -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/expenses.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'expenses.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'expenses.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-money-bill-wave mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Expenses</span>
                    </a>

                    <!-- Categories -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/categories.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'categories.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'categories.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-tags mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Categories</span>
                    </a>

                    <!-- Reports -->
                    <a href="<?php echo BASE_URL . $dash_prefix; ?>/reports.php" 
                       class="flex items-center p-3 rounded-lg transition <?php echo $current_page == 'reports.php' ? 'bg-nskblue text-white shadow-md font-semibold' : 'hover:bg-nskblue hover:text-white text-gray-200'; ?>"
                       style="display: flex; align-items: center; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 0.25rem; <?php echo $current_page == 'reports.php' ? 'background-color: #1e40af; color: white; font-weight: 600;' : 'color: rgba(255,255,255,0.8);'; ?>">
                        <i class="fas fa-chart-pie mr-3 w-6 text-center text-lg" style="margin-right: 0.75rem; width: 1.5rem; text-align: center;"></i>
                        <span>Reports</span>
                    </a>
                </nav>
            </div>

            <!-- Bottom Profile Section (Absolute Positioned like Admin) -->
            <div class="absolute bottom-0 left-0 right-0 p-6 z-20 bg-nsknavy bg-opacity-95 pointer-events-none" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1.5rem; z-index: 20; background: rgba(30, 58, 138, 0.95); pointer-events: none;">
                 <!-- Pointer events auto on the container to allow clicking -->
                <div class="bg-nskblue rounded-lg p-4 shadow-lg pointer-events-auto" style="background-color: #1e40af; border-radius: 0.5rem; padding: 1rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); pointer-events: auto;">
                    <div class="flex items-center mb-3" style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center mr-3 shadow-sm" style="width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                            <span class="text-nskblue font-bold text-sm" style="color: #1e40af; font-weight: 700; font-size: 0.875rem;"><?php echo $user_initials; ?></span>
                        </div>
                        <div class="overflow-hidden" style="overflow: hidden;">
                            <p class="text-white text-sm font-semibold truncate" style="color: white; font-size: 0.875rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="text-white text-xs opacity-75 truncate" style="color: white; font-size: 0.75rem; opacity: 0.75; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($user_role); ?></p>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/logout.php<?php echo isset($_SESSION['session_token']) ? '?token=' . urlencode($_SESSION['session_token']) : ''; ?>" 
                       onclick="return confirmLink(event, 'Logout?', 'Are you sure you want to log out?')"
                       class="w-full bg-white text-nskblue py-2 px-4 rounded-lg text-sm font-bold hover:bg-gray-100 transition flex items-center justify-center shadow-sm"
                       style="width: 100%; background: white; color: #1e40af; padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content (Wrapper) -->
        <main class="main-content flex-1 flex flex-col min-h-screen bg-gray-50" style="margin-left: 256px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; background-color: #f9fafb;">
            <!-- Top Navbar -->
            <header class="top-navbar bg-white shadow-sm px-8 py-4 flex items-center border-b-2 border-nskgold sticky top-0 z-20 justify-end"
                    style="background: white; padding: 1rem 2rem; display: flex; align-items: center; justify-content: flex-end; border-bottom: 2px solid #f59e0b; position: sticky; top: 0; z-index: 20;">
                <div class="user-profile flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <span class="block font-bold text-nsknavy text-sm"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="block text-gray-500 text-xs uppercase tracking-wider"><?php echo htmlspecialchars($user_role); ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-nskblue text-white flex items-center justify-center font-bold text-sm shadow-sm">
                        <?php echo $user_initials; ?>
                    </div>
                </div>
            </header>

