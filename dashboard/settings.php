<?php
// Enable error reporting for debugging
// Debugging disabled
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// session_start();
require_once '../config/logger.php'; // Include logger

require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');

$user_id = $_SESSION['user_id'] ?? 1; // Default to 1 for testing
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userRole = ucfirst($_SESSION['user_type'] ?? 'Administrator'); // 'admin' -> 'Administrator'

// Create initials
$nameParts = explode(' ', $userName, 2);
$firstInitial = $nameParts[0][0] ?? 'A';
$lastInitial = isset($nameParts[1]) ? ($nameParts[1][0] ?? '') : '';
$userInitial = strtoupper($firstInitial . $lastInitial); // "AU"

// Check Permissions
$isSuperAdmin = (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['administrator', 'super_admin'])) || (isset($_SESSION['email']) && $_SESSION['email'] === 'abdul@northland.edu.ng');


// Database connection
try {
  require_once '../config/database.php';
  $database = new Database();
  $db = $database->getConnection();
} catch (Exception $e) {
  die("Database connection failed: " . $e->getMessage());
}

// === POST HANDLERS ===

// Handle Profile Update
if (isset($_POST['update_profile'])) {
  try {
    $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $db->prepare($sql);

    $nameParts = explode(' ', $_POST['full_name'], 2);
    $first_name = $nameParts[0];
    $last_name = $nameParts[1] ?? '';

    $stmt->execute([$first_name, $last_name, $_POST['email'], $_POST['phone'], $user_id]);

    // Update session name
    $_SESSION['user_name'] = $_POST['full_name'];
    $userName = $_POST['full_name']; // Update for immediate display

    $_SESSION['success'] = "Profile updated successfully!";

    // --- LOG ACTIVITY ---
    logActivity(
      $db,
      $userName, // Use the *new* name
      "Profile Update",
      "$userName updated their profile information.",
      "fas fa-user-edit",
      "bg-nskblue"
    );
    // --- END LOG ---

  } catch (Exception $e) {
    $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
  }
  // Refresh page to show new info
  header("Location: settings.php");
  exit();
}

// Handle Password Update
if (isset($_POST['update_password'])) {
  try {
    $user_id = $_SESSION['user_id']; // Strict session usage

    if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
      throw new Exception("All password fields are required.");
    }

    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
      throw new Exception("New passwords do not match.");
    }

    // Get current password hash
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      throw new Exception("User not found.");
    }

    if (!password_verify($current_password, $user['password_hash'])) {
      throw new Exception("Incorrect current password.");
    }

    // Update to new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updateStmt->execute([$new_hash, $user_id]);

    $_SESSION['success'] = "Password updated successfully!";

    // --- LOG ACTIVITY ---
    $admin_name = $_SESSION['user_name'] ?? 'Admin';
    logActivity(
      $db,
      $admin_name,
      "Password Change",
      "$admin_name changed their password.",
      "fas fa-key",
      "bg-nskgreen"
    );
    // --- END LOG ---

  } catch (Exception $e) {
    $_SESSION['error'] = "Error updating password: " . $e->getMessage();
  }
  header("Location: settings.php");
  exit();
}

// Handle System Preferences Update
if (isset($_POST['update_system_settings'])) {
  try {
    $settings_to_update = [
      'school_name' => $_POST['school_name'],
      'school_address' => $_POST['school_address'],
      'school_phone' => $_POST['school_phone'],
      'school_email' => $_POST['school_email']
    ];

    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
    $stmt = $db->prepare($sql);

    foreach ($settings_to_update as $key => $value) {
      $stmt->execute([$value, $key]);
    }

    $_SESSION['success'] = "System preferences updated successfully!";

    // --- LOG ACTIVITY ---
    $admin_name = $_SESSION['user_name'] ?? 'Admin';
    logActivity(
      $db,
      $admin_name,
      "Settings Update",
      "$admin_name updated the system preferences.",
      "fas fa-cogs",
      "bg-nskgold"
    );
    // --- END LOG ---

  } catch (Exception $e) {
    $_SESSION['error'] = "Error updating preferences: " . " " . $e->getMessage();
  }
  header("Location: settings.php");
  exit();
}

// Handle Admin User Creation (Super Admin Only)
if (isset($_POST['create_admin_user'])) {
    if (!$isSuperAdmin) {
        $_SESSION['error'] = "Unauthorized access.";
    } else {
        try {
            // Basic Validation
            if (empty($_POST['first_name']) || empty($_POST['email']) || empty($_POST['password'])) {
                throw new Exception("All fields are required.");
            }
            
            // Check if email exists
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$_POST['email']]);
            if ($check->fetch()) {
                throw new Exception("Email already exists.");
            }

            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $username = strtolower($_POST['first_name'] . '.' . $_POST['last_name']);
            // Uniqueness check for username could be added here
            
            $sql = "INSERT INTO users (first_name, last_name, email, username, password_hash, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $username,
                $password_hash,
                $_POST['admin_role']
            ]);
            
            $_SESSION['success'] = "New admin user created successfully!";
            logActivity($db, $_SESSION['user_name'], "Admin User Created", "Created admin: " . $_POST['email'], "fas fa-user-plus", "bg-nskblue");

        } catch (Exception $e) {
            $_SESSION['error'] = "Error creating admin: " . $e->getMessage();
        }
    }
    header("Location: settings.php?tab=admins");
    exit();
}

// Handle Admin User Update (Super Admin Only)
if (isset($_POST['update_admin_user'])) {
    if (!$isSuperAdmin) {
        $_SESSION['error'] = "Unauthorized access. Only Super Admins can manage administrators.";
    } else {
        try {
            $target_admin_id = $_POST['admin_id'];
            $new_role = $_POST['admin_role']; 
            $status = isset($_POST['is_active']) ? 1 : 0;
            
            // Validate role
            if (!in_array($new_role, ['admin', 'administrator'])) {
                 throw new Exception("Invalid role selected.");
            }

            // Prepare Update
            $sql = "UPDATE users SET first_name=?, last_name=?, email=?, user_type=?, is_active=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                 $_POST['first_name'], 
                 $_POST['last_name'], 
                 $_POST['email'], 
                 $new_role, 
                 $status, 
                 $target_admin_id
            ]);

            $_SESSION['success'] = "Admin user updated successfully!";
            
            // Log Activity
            $admin_name = $_SESSION['user_name'] ?? 'Admin';
            logActivity($db, $admin_name, "Admin User Updated", "Updated admin user ID: $target_admin_id", "fas fa-user-shield", "bg-purple-600");

        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating admin: " . $e->getMessage();
        }
    }
    header("Location: settings.php?tab=admins");
    exit();
}


// === DATA FETCHING FOR PAGE LOAD ===

// Fetch current user's info
$userStmt = $db->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
// Combine first and last name for the form
if ($currentUser) {
  $currentUser['full_name'] = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
} else {
  // Fallback if user_id is invalid
  $currentUser = ['full_name' => 'Admin User', 'email' => '', 'phone' => ''];
}


// Fetch all system settings
$settingsStmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Determine Active Tab
$activeTab = $_GET['tab'] ?? 'profile';

// $isSuperAdmin is already defined at top

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings | Northland Schools Kano</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="sidebar.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            nskblue: "#1e40af",
            nsklightblue: "#3b82f6",
            nsknavy: "#1e3a8a",
            nskgold: "#f59e0b",
            nsklight: "#f0f9ff",
            nskgreen: "#10b981",
            nskred: "#ef4444",
          },
        },
      },
    };
  </script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap");

    body {
      font-family: "Montserrat", sans-serif;
      background: #f8fafc;
    }

    .logo-container {
      background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
    }

    .nav-item {
      position: relative;
    }

    .nav-item::after {
      content: "";
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

    .settings-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      animation: fadeIn 0.4s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Include Sidebar -->
  <?php require_once 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content min-h-screen flex flex-col">
    <?php
    $pageTitle = 'System Settings';
    $pageSubtitle = 'Manage your account and system preferences';
    require_once 'header.php';
    ?>

    <!-- Main Layout Container -->
    <div class="px-4 md:px-8 py-8 w-full flex-grow">

       <!-- Alerts (Global) -->
       <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center shadow-sm">
          <i class="fas fa-check-circle mr-3"></i>
          <div>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center shadow-sm">
          <i class="fas fa-exclamation-circle mr-3"></i>
          <div>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
          </div>
        </div>
      <?php endif; ?>


      <div class="flex flex-col lg:flex-row gap-10 items-start">
        
        <!-- Left Sidebar Navigation -->
        <aside class="w-full lg:w-72 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-md overflow-hidden sticky top-6">
                <div class="p-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="font-bold text-gray-700">Settings Menu</h3>
                </div>
                <nav class="p-2 space-y-1">
                    <?php
                    $navItems = [
                        'profile' => ['icon' => 'fas fa-user', 'label' => 'Profile Settings', 'color' => 'text-nskblue'],
                        'security' => ['icon' => 'fas fa-lock', 'label' => 'Account Security', 'color' => 'text-nskgreen'],
                        'system' => ['icon' => 'fas fa-cogs', 'label' => 'System Preferences', 'color' => 'text-nskgold'],
                        'terms' => ['icon' => 'fas fa-calendar-alt', 'label' => 'Academic Terms', 'color' => 'text-nskblue'],
                        // 'admins' handled separately below
                        'database' => ['icon' => 'fas fa-database', 'label' => 'Database Management', 'color' => 'text-purple-600'],
                    ];

                    foreach ($navItems as $key => $item):
                        $isActive = $activeTab === $key;
                        $bgClass = $isActive ? 'bg-blue-50 text-nskblue border-l-4 border-nskblue' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent';
                    ?>
                        <a href="?tab=<?= $key ?>" class="flex items-center px-4 py-3 text-sm font-medium transition-colors <?= $bgClass ?>">
                            <i class="<?= $item['icon'] ?> w-6 <?= $item['color'] ?>"></i>
                            <?= $item['label'] ?>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($isSuperAdmin): 
                         $isActive = $activeTab === 'admins';
                         $bgClass = $isActive ? 'bg-blue-50 text-nskblue border-l-4 border-nskblue' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent';
                    ?>
                        <div class="my-2 border-t border-gray-100"></div> <!-- Separator -->
                        <a href="?tab=admins" class="flex items-center px-4 py-3 text-sm font-medium transition-colors <?= $bgClass ?>">
                            <i class="fas fa-users-cog w-6 text-nskgold"></i>
                            Administrative Users
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </aside>

        <!-- Right Content Area -->
        <div class="flex-1 min-w-0">
            
            <!-- PROFILE TAB -->
            <?php if ($activeTab === 'profile'): ?>
            <section class="settings-card bg-white rounded-xl shadow-md p-6 h-full">
                <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-nskblue flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-user-edit text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">Profile Settings</h2>
                        <p class="text-gray-500 text-sm">Update your personal information and contact details</p>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-6 max-w-3xl">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Full Name</label>
                        <input type="text" name="full_name" class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskblue focus:border-nskblue transition shadow-sm"
                            value="<?= htmlspecialchars($currentUser['full_name']) ?>">
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Email Address</label>
                        <input type="email" name="email" class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskblue focus:border-nskblue transition shadow-sm"
                            value="<?= htmlspecialchars($currentUser['email']) ?>">
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Phone Number</label>
                        <input type="text" name="phone" class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskblue focus:border-nskblue transition shadow-sm"
                            value="<?= htmlspecialchars($currentUser['phone']) ?>">
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Profile Picture</label>
                        <div class="flex items-center p-1 border rounded-lg bg-gray-50">
                            <div class="w-12 h-12 rounded-full bg-nsklightblue flex items-center justify-center text-white mr-3 ml-1">
                                <span class="font-bold text-lg"><?= $userInitial ?></span>
                            </div>
                            <input type="file" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-nskblue hover:file:bg-blue-100">
                        </div>
                        </div>
                    </div>
                    <div class="pt-4">
                        <button type="submit" name="update_profile"
                            class="bg-nskblue text-white px-8 py-3 rounded-lg font-semibold hover:bg-nsknavy transition shadow-md hover:shadow-lg flex items-center">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- SECURITY TAB -->
            <?php if ($activeTab === 'security'): ?>
            <section class="settings-card bg-white rounded-xl shadow-md p-6 h-full">
                <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-nskgreen flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-shield-alt text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">Account Security</h2>
                        <p class="text-gray-500 text-sm">Manage your password and security preferences</p>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-6 max-w-2xl">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i class="fas fa-info-circle text-yellow-600 mt-1 mr-3"></i>
                            <p class="text-sm text-yellow-800">Ensure your password is at least 8 characters long and includes a mix of letters, numbers, and symbols.</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Current Password</label>
                        <input type="password" name="current_password"
                            class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgreen focus:border-nskgreen transition shadow-sm" required>
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">New Password</label>
                        <input type="password" name="new_password"
                            class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgreen focus:border-nskgreen transition shadow-sm" required>
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password"
                            class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgreen focus:border-nskgreen transition shadow-sm" required>
                        </div>
                    </div>
                    <div class="pt-4">
                        <button type="submit" name="update_password"
                            class="bg-nskgreen text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-600 transition shadow-md hover:shadow-lg flex items-center">
                            <i class="fas fa-key mr-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- SYSTEM TAB -->
            <?php if ($activeTab === 'system'): ?>
            <section class="settings-card bg-white rounded-xl shadow-md p-6 h-full">
                <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-nskgold flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-server text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">System Preferences</h2>
                        <p class="text-gray-500 text-sm">Global settings regarding school identity</p>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-6 max-w-3xl">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-semibold text-gray-700">School Name</label>
                        <input type="text" name="school_name" class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgold focus:border-nskgold transition shadow-sm"
                            value="<?= htmlspecialchars($settings['school_name'] ?? '') ?>">
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">School Phone</label>
                        <input type="text" name="school_phone"
                            class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgold focus:border-nskgold transition shadow-sm"
                            value="<?= htmlspecialchars($settings['school_phone'] ?? '') ?>">
                        </div>
                        <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-700">School Email</label>
                        <input type="email" name="school_email"
                            class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgold focus:border-nskgold transition shadow-sm"
                            value="<?= htmlspecialchars($settings['school_email'] ?? '') ?>">
                        </div>
                        <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-semibold text-gray-700">School Address</label>
                        <input type="text" name="school_address"
                            class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-nskgold focus:border-nskgold transition shadow-sm"
                            value="<?= htmlspecialchars($settings['school_address'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="pt-4">
                        <button type="submit" name="update_system_settings"
                            class="bg-nskgold text-white px-8 py-3 rounded-lg font-semibold hover:bg-amber-600 transition shadow-md hover:shadow-lg flex items-center">
                            <i class="fas fa-globe mr-2"></i>Save System Preferences
                        </button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- TERMS TAB -->
            <?php if ($activeTab === 'terms'): ?>
            <section class="settings-card bg-white rounded-xl shadow-md p-6 h-full">
                <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-nskblue flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-calendar-check text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">Academic Terms</h2>
                        <p class="text-gray-500 text-sm">Manage school terms and calendar synchronization</p>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <div class="p-6 bg-blue-50 rounded-xl border border-nsklightblue/30">
                        <h3 class="font-bold text-nskblue mb-4 flex items-center">
                            <i class="fas fa-flag mr-2"></i> Nigerian Academic Calendar
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">Standard term dates as per ministry guidelines:</p>
                        <ul class="text-sm text-gray-700 mb-6 space-y-3 bg-white/50 p-4 rounded-lg">
                            <li class="flex justify-between border-b pb-2 border-dashed border-blue-200">
                                <span><strong>First Term:</strong></span> 
                                <span>Sep 9 - Dec 13 (96 days)</span>
                            </li>
                            <li class="flex justify-between border-b pb-2 border-dashed border-blue-200">
                                <span><strong>Second Term:</strong></span> 
                                <span>Jan 6 - Apr 11 (95 days)</span>
                            </li>
                            <li class="flex justify-between">
                                <span><strong>Third Term:</strong></span> 
                                <span>Apr 28 - Jul 25 (88 days)</span>
                            </li>
                        </ul>
                        <a href="term-management.php" class="inline-flex items-center justify-center w-full bg-nsklightblue text-white px-4 py-3 rounded-lg font-semibold hover:bg-nskblue transition shadow-sm">
                            Go to Term Management <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    <div class="p-6 bg-green-50 rounded-xl border border-nskgreen/30">
                        <h3 class="font-bold text-nskgreen mb-4 flex items-center">
                            <i class="fas fa-check-circle mr-2"></i> Capabilities
                        </h3>
                        <ul class="text-sm text-gray-700 space-y-3">
                            <li class="flex items-start"><i class="fas fa-check text-nskgreen mt-1 mr-2"></i> Activate/Deactivate current terms</li>
                            <li class="flex items-start"><i class="fas fa-check text-nskgreen mt-1 mr-2"></i> Edit start/end dates for custom schedules</li>
                            <li class="flex items-start"><i class="fas fa-check text-nskgreen mt-1 mr-2"></i> Sync automatically with official calendar</li>
                            <li class="flex items-start"><i class="fas fa-check text-nskgreen mt-1 mr-2"></i> Student promotion Management</li>
                        </ul>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- ADMINS TAB -->
            <?php if ($activeTab === 'admins'): 
                // Security check again for sanity, though logic above handles menu visibility
                if ($isSuperAdmin):
            ?>
            <section class="settings-card bg-white rounded-xl shadow-md p-6 h-full">
                <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-nskgold flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-users-cog text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">Administrative Users</h2>
                        <p class="text-gray-500 text-sm">Manage system administrators and super admins</p>
                    </div>
                </div>

                <div class="mb-4 flex justify-end">
                    <button onclick="openAdminModal()" class="bg-nskblue text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-nsknavy transition shadow-sm flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Admin
                    </button>
                </div>
                
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                        <?php
                        $adminStmt = $db->query("SELECT * FROM users WHERE user_type IN ('admin', 'administrator', 'super_admin') ORDER BY user_type DESC");
                        while ($admin = $adminStmt->fetch(PDO::FETCH_ASSOC)):
                            $safeAdminJson = htmlspecialchars(json_encode($admin), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-nskblue font-bold text-xs">
                                        <?= strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)) ?>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></div>
                                        <div class="text-xs text-gray-500">@<?= htmlspecialchars($admin['username']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($admin['email']) ?></td>
                            <td class="px-6 py-4">
                                <?php if ($admin['user_type'] === 'administrator'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Super Admin
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Admin
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($admin['is_active']): ?>
                                <span class="inline-flex items-center text-green-600 text-sm font-medium"><i class="fas fa-circle text-[8px] mr-2"></i> Active</span>
                                <?php else: ?>
                                <span class="inline-flex items-center text-red-600 text-sm font-medium"><i class="fas fa-circle text-[8px] mr-2"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" 
                                    onclick="openAdminModal(this)" 
                                    data-admin='<?= $safeAdminJson ?>'
                                    class="text-indigo-600 hover:text-indigo-900 font-medium text-sm transition-colors px-3 py-1 bg-indigo-50 rounded-md hover:bg-indigo-100">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php else: ?>
                <div class="p-8 text-center text-gray-500">Access Restricted</div>
            <?php endif; endif; ?>

            <!-- DATABASE TAB -->
            <?php if ($activeTab === 'database'): ?>
            <section class="settings-card bg-white rounded-xl shadow-md p-6 h-full">
                <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-database text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-nsknavy">Database Management</h2>
                        <p class="text-gray-500 text-sm">Backup, restore, and manage system data</p>
                    </div>
                </div>

                <!-- Actions Grid -->
                <div class="grid lg:grid-cols-3 gap-6 mb-8">
                    <!-- Backup -->
                    <div class="p-6 bg-blue-50 rounded-xl border border-blue-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-nsklightblue mb-4 shadow-sm">
                            <i class="fas fa-download text-xl"></i>
                        </div>
                        <h3 class="font-bold text-nsknavy mb-2">Backup Database</h3>
                        <p class="text-sm text-gray-600 mb-6 min-h-[40px]">Download a complete SQL dump of the current system.</p>
                        <form method="POST" action="database_operations.php" onsubmit="return confirmSubmit(event, 'Create Backup?', 'This will generate a new SQL backup file.')">
                            <input type="hidden" name="backup_database" value="1">
                            <button type="submit" class="w-full bg-nsklightblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nskblue transition">
                                Create Backup
                            </button>
                        </form>
                    </div>

                    <!-- Restore -->
                    <div class="p-6 bg-green-50 rounded-xl border border-green-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-nskgreen mb-4 shadow-sm">
                            <i class="fas fa-upload text-xl"></i>
                        </div>
                        <h3 class="font-bold text-nsknavy mb-2">Restore Backup</h3>
                        <p class="text-sm text-gray-600 mb-6 min-h-[40px]">Restore system from a previous SQL backup file.</p>
                        <form method="POST" action="database_operations.php" enctype="multipart/form-data" onsubmit="return confirmSubmit(event, 'Restore Database?', '⚠️ WARNING: This will replace ALL current data with the backup!', 'Yes, Restore it!', '#ef4444')">
                            <div class="relative mb-3">
                                <input type="hidden" name="restore_database" value="1">
                                <input type="file" name="backup_file" accept=".sql" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-100 file:text-green-700 hover:file:bg-green-200">
                            </div>
                            <button type="submit" class="w-full bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition">
                                Restore Data
                            </button>
                        </form>
                    </div>

                    <!-- Clear -->
                    <div class="p-6 bg-red-50 rounded-xl border border-red-100 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-nskred mb-4 shadow-sm">
                            <i class="fas fa-trash-alt text-xl"></i>
                        </div>
                        <h3 class="font-bold text-nsknavy mb-2">Clear Database</h3>
                        <p class="text-sm text-gray-600 mb-6 min-h-[40px]">Reset system data while preserving admin accounts.</p>
                        <button onclick="showClearConfirmModal()" type="button" class="w-full bg-nskred text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600 transition">
                            Reset System
                        </button>
                    </div>
                </div>

                <!-- Backups Table -->
                <div class="border rounded-xl overflow-hidden mt-8">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h3 class="font-bold text-gray-700 flex items-center">
                            <i class="fas fa-history text-gray-400 mr-2"></i> Recent Backups
                        </h3>
                    </div>
                    <div class="overflow-y-auto" style="max-height: 250px;">
                    <table class="w-full text-left relative">
                        <thead class="bg-gray-50 border-b sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Filename</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Size</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                        <?php
                        $backup_dir = dirname(__DIR__) . '/backups/';
                        if (is_dir($backup_dir)) {
                            $files = scandir($backup_dir);
                            $backups = [];
                            foreach ($files as $file) {
                                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                                    $backups[] = [
                                        'file' => $file,
                                        'time' => filemtime($backup_dir . $file)
                                    ];
                                }
                            }
                            
                            // Sort by time descending (newest first)
                            usort($backups, function($a, $b) {
                                return $b['time'] - $a['time'];
                            });

                            if (empty($backups)) {
                                echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No backups found.</td></tr>';
                            } else {
                                foreach ($backups as $backup_data) {
                                    $backup = $backup_data['file'];
                                    if (pathinfo($backup, PATHINFO_EXTENSION) === 'sql') {
                                        $filepath = $backup_dir . $backup;
                                        $size = filesize($filepath);
                                        $size_formatted = $size > 1048576 ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 2) . ' KB';
                                        $date = date('M d, Y h:i A', filemtime($filepath));
                                        
                                        echo '<tr class="hover:bg-gray-50">';
                                        echo '<td class="px-6 py-4 text-sm font-medium text-gray-900"><i class="fas fa-file-code text-gray-400 mr-2"></i>' . htmlspecialchars($backup) . '</td>';
                                        echo '<td class="px-6 py-4 text-sm text-gray-500">' . $size_formatted . '</td>';
                                        echo '<td class="px-6 py-4 text-sm text-gray-500">' . $date . '</td>';
                                        echo '<td class="px-6 py-4 text-right space-x-2">';
                                        echo '<a href="database_operations.php?download=' . urlencode($backup) . '" class="text-blue-600 hover:text-blue-900" title="Download"><i class="fas fa-download"></i></a>';
                                        echo '<form method="POST" action="database_operations.php" class="inline" onsubmit="return confirmSubmit(event, \'Delete Backup?\', \'This file will be permanently deleted.\', \'Yes, delete it!\', \'#d33\')">';
                                        echo '<input type="hidden" name="backup_filename" value="' . htmlspecialchars($backup) . '">';
                                        echo '<input type="hidden" name="delete_backup" value="1">';
                                        echo '<button type="submit" class="text-red-600 hover:text-red-900 ml-3" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                                        echo '</form>';
                                        echo '</td></tr>';
                                    }
                                }
                            }
                        } else {
                            echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Backup directory missing.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>

        </div> <!-- End Right Content -->
      </div>
    </div> <!-- End Main Container -->

    <?php require_once 'footer.php'; ?>
    
    <!-- Admin Modal (Add/Edit) -->
    <div id="adminModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[1001] backdrop-blur-sm" onclick="if(event.target.id === 'adminModal') closeAdminModal()">
        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-lg w-full mx-4 transform transition-all scale-100" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h3 class="text-xl font-bold text-nsknavy" id="modalTitle">Add New Administrator</h3>
                <button onclick="closeAdminModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" id="adminForm">
                <!-- Dynamic Hidden Inputs -->
                <input type="hidden" name="create_admin_user" id="action_create" value="1">
                <input type="hidden" name="update_admin_user" id="action_update" disabled>
                <input type="hidden" name="admin_id" id="edit_admin_id">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" required class="w-full border-gray-300 rounded-lg p-2.5 focus:border-nskblue focus:ring-1 focus:ring-nskblue transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" required class="w-full border-gray-300 rounded-lg p-2.5 focus:border-nskblue focus:ring-1 focus:ring-nskblue transition">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" id="edit_email" required class="w-full border-gray-300 rounded-lg p-2.5 focus:border-nskblue focus:ring-1 focus:ring-nskblue transition">
                </div>
                
                <!-- Password Field (Only required for new users, optional for edit) -->
                <div class="mb-4">
                     <label class="block text-sm font-medium text-gray-700 mb-1" id="passwordLabel">Password</label>
                     <input type="password" name="password" id="edit_password" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-nskblue focus:ring-1 focus:ring-nskblue transition">
                     <p class="text-xs text-gray-500 mt-1 hidden" id="passwordHint">Leave blank to keep current password</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="admin_role" id="edit_role" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-nskblue focus:ring-1 focus:ring-nskblue transition">
                        <option value="admin">Admin</option>
                        <option value="administrator">Super Admin</option>
                    </select>
                </div>
                
                <div class="mb-6 bg-gray-50 p-4 rounded-lg flex items-center justify-between" id="statusContainer">
                    <div>
                        <span class="block text-sm font-medium text-gray-900">Account Status</span>
                        <span class="block text-xs text-gray-500">Enable or disable this user access</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-nskblue"></div>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeAdminModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-nsknavy text-white rounded-lg hover:bg-blue-900 font-medium transition shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
  </main>

  <!-- Scripts -->
  <script>
    function openAdminModal(btn = null) {
        const modal = document.getElementById('adminModal');
        const form = document.getElementById('adminForm');
        
        // Reset Form
        form.reset();
        
        if (btn) {
            // EDIT MODE
            const data = JSON.parse(btn.getAttribute('data-admin'));
            
            document.getElementById('modalTitle').textContent = 'Edit Administrator';
            document.getElementById('edit_admin_id').value = data.id;
            
            // Switch Actions
            document.getElementById('action_create').disabled = true;
            document.getElementById('action_update').disabled = false;
            document.getElementById('action_update').value = '1';
            
            // Populate Fields
            document.getElementById('edit_first_name').value = data.first_name;
            document.getElementById('edit_last_name').value = data.last_name;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_role').value = data.user_type; 
            document.getElementById('edit_is_active').checked = (data.is_active == 1);
            
            // Password Handling
            document.getElementById('edit_password').required = false;
            document.getElementById('passwordLabel').textContent = 'Change Password (Optional)';
            document.getElementById('passwordHint').classList.remove('hidden');
            
        } else {
            // CREATE MODE
            document.getElementById('modalTitle').textContent = 'Add New Administrator';
            document.getElementById('edit_admin_id').value = '';
            
            // Switch Actions
            document.getElementById('action_create').disabled = false;
            document.getElementById('action_update').disabled = true;
            
            // Defaults
            document.getElementById('edit_role').value = 'admin';
            document.getElementById('edit_is_active').checked = true;
            
            // Password Handling
            document.getElementById('edit_password').required = true;
            document.getElementById('passwordLabel').textContent = 'Password';
            document.getElementById('passwordHint').classList.add('hidden');
        }
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAdminModal() {
        const modal = document.getElementById('adminModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      // Clear Database Confirmation Modal (Retained Logic)
      window.showClearConfirmModal = function() {
        const modalHTML = `
          <div id="clearDbModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm transition-all duration-300" onclick="if(event.target.id === 'clearDbModal') closeClearModal()">
            <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 transform scale-100 transition-all" onclick="event.stopPropagation()">
              <div class="text-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                  <i class="fas fa-exclamation-triangle text-nskred text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Critical Action</h3>
                <p class="text-gray-600">You are about to clear the system database.</p>
              </div>
              
              <div class="bg-red-50 border border-red-100 rounded-lg p-4 mb-6">
                <ul class="text-sm text-red-800 space-y-2 list-disc list-inside">
                  <li><strong>All Student Data</strong> will be erased</li>
                  <li><strong>All Results</strong> will be erased</li>
                  <li>Admin accounts will be <strong>preserved</strong></li>
                  <li>System settings will be <strong>preserved</strong></li>
                </ul>
              </div>

              <form method="POST" action="database_operations.php" id="clearDbForm">
                <label class="block mb-2 text-sm font-semibold text-gray-700">
                  Type <span class="font-bold text-red-600">CLEAR</span> to confirm:
                </label>
                <input type="text" name="confirm_clear" id="confirmClearInput" 
                       class="w-full border-2 border-gray-300 rounded-lg p-3 mb-6 focus:border-red-500 focus:ring-red-500 outline-none transition uppercase"
                       placeholder="CLEAR" required>
                
                <div class="flex gap-3">
                  <button type="button" onclick="closeClearModal()" 
                          class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                    Cancel
                  </button>
                  <button type="submit"
                          class="flex-1 bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-3 rounded-lg font-semibold hover:from-red-700 hover:to-red-800 shadow-lg transition">
                    Reset Data
                  </button>
                  <input type="hidden" name="clear_database" value="1">
                </div>
              </form>
            </div>
          </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        setTimeout(() => document.getElementById('confirmClearInput').focus(), 100);
      }

      window.closeClearModal = function() {
        const modal = document.getElementById('clearDbModal');
        if (modal) modal.remove();
      }

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeClearModal();
      });
    });
  </script>
</body>
</html>
