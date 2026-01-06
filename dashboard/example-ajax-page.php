<?php
/**
 * Example AJAX-Compatible Page
 * 
 * This demonstrates how to structure a dashboard page for AJAX navigation
 */

// 1. Auth check (required for all pages)
require_once 'auth-check.php';
checkAuth('admin');

// 2. Include AJAX helper (MUST be after auth-check)
require_once 'ajax-helper.php';

// 3. Set page title
$pageTitle = 'Example Page';
$pageSubtitle = 'This is an example of AJAX-compatible page';

// 4. Handle POST requests (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Your POST logic here
    if (isset($_POST['some_action'])) {
        // Process action
        $_SESSION['success'] = 'Action completed!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 5. Fetch data for the page
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Your data queries here
    $exampleData = [];
    
} catch (Exception $e) {
    $exampleData = [];
    $_SESSION['error'] = 'Error loading data';
}

// 6. Include layout header (outputs HTML structure for non-AJAX requests)
require_once 'layout-header.php';
?>

<!-- 7. YOUR CONTENT GOES HERE -->
<!-- Everything inside #main-content will be sent for AJAX requests -->

<div class="p-6">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 flex items-center">
            <div class="bg-nsklightblue p-4 rounded-full mr-4">
                <i class="fas fa-users text-white text-xl"></i>
            </div>
            <div>
                <p class="text-gray-600">Total Users</p>
                <p class="text-2xl font-bold text-nsknavy">450</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-5 flex items-center">
            <div class="bg-nskgreen p-4 rounded-full mr-4">
                <i class="fas fa-check text-white text-xl"></i>
            </div>
            <div>
                <p class="text-gray-600">Active Sessions</p>
                <p class="text-2xl font-bold text-nsknavy">125</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-5 flex items-center">
            <div class="bg-nskgold p-4 rounded-full mr-4">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <div>
                <p class="text-gray-600">Growth Rate</p>
                <p class="text-2xl font-bold text-nsknavy">23%</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-5 flex items-center">
            <div class="bg-nskred p-4 rounded-full mr-4">
                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
            </div>
            <div>
                <p class="text-gray-600">Alerts</p>
                <p class="text-2xl font-bold text-nsknavy">5</p>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-nsknavy mb-4">Example Content</h2>
        <p class="text-gray-600 mb-4">
            This page demonstrates AJAX navigation. Click any sidebar link to see
            only this content area reload without the sidebar refreshing.
        </p>

        <!-- Example Form -->
        <form method="POST" class="space-y-4 max-w-md">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Example Input
                </label>
                <input 
                    type="text" 
                    name="example_input" 
                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-nskblue focus:border-nskblue"
                    placeholder="Type something..."
                />
            </div>

            <button 
                type="submit" 
                name="some_action" 
                class="bg-nskblue text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-nsknavy transition"
            >
                <i class="fas fa-save mr-2"></i> Submit
            </button>
        </form>
    </div>
</div>

<!-- Page-specific JavaScript (optional) -->
<script>
    // This script will be re-executed after AJAX navigation
    console.log('Example page loaded!');
    
    // Listen for AJAX navigation complete event
    window.addEventListener('ajaxNavigationComplete', function(e) {
        console.log('AJAX navigation completed, content refreshed');
        // Re-initialize any page-specific functionality here
    });
</script>

<?php
// 8. Include layout footer (closes HTML structure for non-AJAX requests)
require_once 'layout-footer.php';
?>
