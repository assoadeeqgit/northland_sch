<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.5;
      }
    }

    .settings-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .settings-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="flex">
  <!-- UPDATED: Include the new sidebar.php file -->
  <?php require_once 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <?php
    $pageTitle = 'System Settings';
    $pageSubtitle = 'Manage your account and system preferences';
    require_once 'header.php';
    ?>

    <!-- Settings Content -->
    <div class="p-4 md:p-6">

      <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <?= $_SESSION['success'];
          unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <?= $_SESSION['error'];
          unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <!-- Profile Settings -->
      <section class="settings-card bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center mb-6">
          <div class="w-12 h-12 rounded-full bg-nskblue flex items-center justify-center text-white mr-4">
            <i class="fas fa-user text-xl"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-nsknavy">Profile Settings</h2>
            <p class="text-gray-600">Update your personal information</p>
          </div>
        </div>

        <form method="POST" action="" class="space-y-4">
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2 font-medium text-nsknavy">Full Name</label>
              <input type="text" name="full_name" class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($currentUser['full_name']) ?>">
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">Email Address</label>
              <input type="email" name="email" class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($currentUser['email']) ?>">
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">Phone Number</label>
              <input type="text" name="phone" class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($currentUser['phone']) ?>">
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">Profile Picture</label>
              <div class="flex items-center">
                <div class="w-16 h-16 rounded-full bg-nsklightblue flex items-center justify-center text-white mr-4">
                  <i class="fas fa-user text-xl"></i>
                </div>
                <input type="file" class="w-full border rounded-lg p-2 focus:border-nskblue transition">
              </div>
            </div>
          </div>
          <button type="submit" name="update_profile"
            class="bg-nskblue text-white px-6 py-3 rounded-lg font-semibold hover:bg-nsknavy transition">
            <i class="fas fa-save mr-2"></i>Save Changes
          </button>
        </form>
      </section>

      <!-- Account Settings -->
      <section class="settings-card bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center mb-6">
          <div class="w-12 h-12 rounded-full bg-nskgreen flex items-center justify-center text-white mr-4">
            <i class="fas fa-lock text-xl"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-nsknavy">Account Security</h2>
            <p class="text-gray-600">Change your password and security settings</p>
          </div>
        </div>

        <form method="POST" action="" class="space-y-4">
          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2 font-medium text-nsknavy">Current Password</label>
              <input type="password" name="current_password"
                class="w-full border rounded-lg p-3 focus:border-nskblue transition" required>
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">New Password</label>
              <input type="password" name="new_password"
                class="w-full border rounded-lg p-3 focus:border-nskblue transition" required>
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">Confirm Password</label>
              <input type="password" name="confirm_password"
                class="w-full border rounded-lg p-3 focus:border-nskblue transition" required>
            </div>
          </div>
          <button type="submit" name="update_password"
            class="bg-nskgreen text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition">
            <i class="fas fa-key mr-2"></i>Update Password
          </button>
        </form>
      </section>

      <!-- System Preferences -->
      <section class="settings-card bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center mb-6">
          <div class="w-12 h-12 rounded-full bg-nskgold flex items-center justify-center text-white mr-4">
            <i class="fas fa-cogs text-xl"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-nsknavy">System Preferences</h2>
            <p class="text-gray-600">Manage global settings for the school</p>
          </div>
        </div>

        <form method="POST" action="" class="space-y-4">
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2 font-medium text-nsknavy">School Name</label>
              <input type="text" name="school_name" class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($settings['school_name'] ?? '') ?>">
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">School Email Address</label>
              <input type="email" name="school_email"
                class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($settings['school_email'] ?? '') ?>">
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">School Phone</label>
              <input type="text" name="school_phone"
                class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($settings['school_phone'] ?? '') ?>">
            </div>
            <div>
              <label class="block mb-2 font-medium text-nsknavy">School Address</label>
              <input type="text" name="school_address"
                class="w-full border rounded-lg p-3 focus:border-nskblue transition"
                value="<?= htmlspecialchars($settings['school_address'] ?? '') ?>">
            </div>
          </div>
          <button type="submit" name="update_system_settings"
            class="bg-nskgold text-white px-6 py-3 rounded-lg font-semibold hover:bg-amber-600 transition">
            <i class="fas fa-save mr-2"></i>Save System Preferences
          </button>
        </form>
      </section>

      <!-- Term Management Settings -->
      <section class="settings-card bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center mb-6">
          <div class="w-12 h-12 rounded-full bg-nskblue flex items-center justify-center text-white mr-4">
            <i class="fas fa-calendar-alt text-xl"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-nsknavy">Academic Terms</h2>
            <p class="text-gray-600">Configure and manage school terms</p>
          </div>
        </div>
        <div class="space-y-4">
          <div class="p-4 bg-blue-50 rounded-lg border border-nsklightblue">
            <h3 class="font-semibold text-nskblue mb-3">Nigerian Academic Calendar</h3>
            <p class="text-sm text-gray-600 mb-4">Standard Nigerian school terms:</p>
            <ul class="text-sm text-gray-600 mb-4 list-disc list-inside space-y-1">
              <li><strong>First Term:</strong> September 9 - December 13 (96 days)</li>
              <li><strong>Second Term:</strong> January 6 - April 11 (95 days)</li>
              <li><strong>Third Term:</strong> April 28 - July 25 (88 days)</li>
            </ul>
            <a href="term-management.php" class="inline-block bg-nsklightblue text-white px-4 py-2 rounded font-semibold hover:bg-nskblue transition">
              <i class="fas fa-cog mr-2"></i>Go to Term Management
            </a>
          </div>
          <div class="p-4 bg-green-50 rounded-lg border border-nskgreen">
            <h3 class="font-semibold text-nskgreen mb-2"><i class="fas fa-check-circle mr-2"></i>Features Available:</h3>
            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
              <li>Activate/Deactivate terms</li>
              <li>Edit term start and end dates</li>
              <li>Sync with Nigerian academic calendar</li>
              <li>Promote students to next class</li>
              <li>View term duration and status</li>
            </ul>
          </div>
        </div>
      </section>

      <!-- Other sections (Theme, Language, Notifications) are static HTML -->
      <!-- <section class="settings-card bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center mb-6">
          <div class="w-12 h-12 rounded-full bg-nskblue flex items-center justify-center text-white mr-4">
            <i class="fas fa-palette text-xl"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-nsknavy">Appearance</h2>
            <p class="text-gray-600">Customize your system experience</p>
          </div>
        </div>
        <div class="space-y-6">
          <div class="flex flex-col md:flex-row items-center justify-between gap-4 p-4 bg-nsklight rounded-lg">
            <div class="flex items-center">
              <i class="fas fa-moon text-nskblue text-xl mr-4"></i>
              <div>
                <label class="block font-medium text-nsknavy">Theme</label>
                <p class="text-sm text-gray-600">Toggle between Light and Dark mode</p>
              </div>
            </div>
            <button id="darkModeToggle" class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition">
              <i class="fas fa-moon mr-2"></i>Toggle Theme
            </button>
          </div>
        </div>
      </section>
       -->
    </div>
  </main>


  <!-- Scripts -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Note: The sidebar.php file handles its own initialization
      // so we don't need to call sidebarManager.init() here anymore.
      // We only keep the code that is UNIQUE to this page.

      // Dark mode toggle functionality
      const toggleTheme = document.getElementById('darkModeToggle');

      function toggleDarkMode() {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');

        // Update button text
        if (document.body.classList.contains('dark')) {
          toggleTheme.innerHTML = '<i class="fas fa-sun mr-2"></i>Light Mode';
        } else {
          toggleTheme.innerHTML = '<i class="fas fa-moon mr-2"></i>Dark Mode';
        }
      }

      if (toggleTheme) {
        toggleTheme.addEventListener('click', toggleDarkMode);
      }

      // Load theme from localStorage
      if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        if (toggleTheme) {
          toggleTheme.innerHTML = '<i class="fas fa-sun mr-2"></i>Light Mode';
        }
      }
    });
  </script>
</body>
<!-- Include footer -->
<script src="footer.js"></script>

</html>