<?php
/**
 * Teacher Settings Page
 * Allows teachers to update their bank information and account settings
 */

// Prevent HTML caching but allow resource caching
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Start session and check authentication
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php';
require_once 'security.php';

// Initialize session security
SessionSecurity::init();

class TeacherSettings
{
    private $conn;
    private $teacher_user_id;
    private $teacher_id;
    private $error_message = null;
    private $success_message = null;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed");
        }

        $this->teacher_user_id = $_SESSION['user_id'];
        $this->setTeacherId();
    }

    private function setTeacherId()
    {
        $query = "SELECT id FROM teachers WHERE user_id = :teacher_user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
        $stmt->execute();
        $this->teacher_id = $stmt->fetchColumn();

        if (!$this->teacher_id) {
            throw new Exception("Teacher profile not found");
        }
    }

    /**
     * Get teacher's profile information
     */
    public function getTeacherProfile(): array
    {
        $profile = [
            'user_id' => '',
            'username' => '',
            'email' => '',
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'date_of_birth' => '',
            'gender' => '',
            'address' => '',
            'profile_picture' => '',
            'teacher_id' => '',
            'qualification' => '',
            'specialization' => '',
            'employment_date' => '',
            'bank_name' => '',
            'account_number' => '',
            'subject_specialization' => '',
            'years_experience' => '',
            'department' => '',
            'employment_type' => ''
        ];

        $query = "
            SELECT 
                u.*,
                t.teacher_id,
                t.qualification,
                t.specialization,
                t.employment_date,
                t.bank_name,
                t.account_number,
                tp.subject_specialization,
                tp.years_experience,
                tp.department,
                tp.employment_type
            FROM users u
            LEFT JOIN teachers t ON u.id = t.user_id
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.id = :teacher_user_id
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $profile = [
                    'user_id' => $data['id'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'initials' => strtoupper(substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1)),
                    'phone' => $data['phone'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'],
                    'address' => $data['address'],
                    'profile_picture' => $data['profile_picture'],
                    'teacher_id' => $data['teacher_id'],
                    'qualification' => $data['qualification'],
                    'specialization' => $data['specialization'],
                    'employment_date' => $data['employment_date'],
                    'bank_name' => $data['bank_name'],
                    'account_number' => $data['account_number'],
                    'subject_specialization' => $data['subject_specialization'],
                    'years_experience' => $data['years_experience'],
                    'department' => $data['department'],
                    'employment_type' => $data['employment_type']
                ];
            }
        } catch (PDOException $e) {
            error_log("Error fetching teacher profile: " . $e->getMessage());
        }

        return $profile;
    }



    /**
     * Update teacher's account settings (email and password)
     */
    public function updateAccountSettings(array $data): bool
    {
        try {
            $this->conn->beginTransaction();

            // Verify current password if changing password
            if (!empty($data['current_password']) && !empty($data['new_password'])) {
                $verify_query = "SELECT password_hash FROM users WHERE id = :user_id";
                $stmt_verify = $this->conn->prepare($verify_query);
                $stmt_verify->execute([':user_id' => $this->teacher_user_id]);
                $current_hash = $stmt_verify->fetchColumn();

                if (!$current_hash || !password_verify($data['current_password'], $current_hash)) {
                    $this->error_message = "Current password is incorrect";
                    $this->conn->rollBack();
                    return false;
                }

                // Update password
                $password_query = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
                $stmt_password = $this->conn->prepare($password_query);
                $new_password_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
                $stmt_password->execute([
                    ':password_hash' => $new_password_hash,
                    ':user_id' => $this->teacher_user_id
                ]);
            }

            // Update email if provided
            if (!empty($data['email'])) {
                $new_email = trim($data['email']);
                
                if ($new_email !== $data['current_email']) {
                    // Validate email format
                    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                        $this->error_message = "Invalid email address format";
                        $this->conn->rollBack();
                        return false;
                    }
                    
                    // Check if email already exists
                    $email_check = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                    $stmt_check = $this->conn->prepare($email_check);
                    $stmt_check->execute([
                        ':email' => $new_email,
                        ':user_id' => $this->teacher_user_id
                    ]);

                    if ($stmt_check->fetch()) {
                        $this->error_message = "Email address is already in use";
                        $this->conn->rollBack();
                        return false;
                    }

                    $email_query = "UPDATE users SET email = :email WHERE id = :user_id";
                    $stmt_email = $this->conn->prepare($email_query);
                    $stmt_email->execute([
                        ':email' => $new_email,
                        ':user_id' => $this->teacher_user_id
                    ]);
                }
            }

            $this->conn->commit();
            $this->success_message = "Account settings updated successfully!";
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->error_message = "Error updating account settings: " . $e->getMessage();
            error_log("Update account settings error: " . $e->getMessage());
            return false;
        }
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function getSuccessMessage(): ?string
    {
        return $this->success_message;
    }
}

// --- Execution & Form Handling ---
try {
    $database = new Database();
    $teacher_settings = new TeacherSettings($database);

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $is_success = false;

        switch ($action) {
            case 'update_account':
                CSRF::validateRequest();
                $is_success = $teacher_settings->updateAccountSettings($_POST);
                break;
        }

        // Set session messages for display after redirect
        if ($is_success) {
            $_SESSION['success_message'] = $teacher_settings->getSuccessMessage();
        } else {
            $_SESSION['error_message'] = $teacher_settings->getErrorMessage();
        }

        // Redirect to avoid form resubmission
        header("Location: settings.php");
        exit;
    }

    // Get teacher profile data
    $profile = $teacher_settings->getTeacherProfile();

    // Check for session messages
    if (isset($_SESSION['success_message'])) {
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
} catch (Exception $e) {
    error_log("Teacher settings page error: " . $e->getMessage());
    if (strpos($e->getMessage(), 'Teacher profile not found') !== false) {
        session_destroy();
        header("Location: ../login-form.php?error=auth");
        exit();
    }
    $error_message = "An error occurred while loading the settings page.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: all 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -100%;
                z-index: 50;
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
                z-index: 40;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
        
        .sidebar-link.active {
            background-color: #1e40af !important;
        }
        
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 10000;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            opacity: 0;
            transform: translateY(-20px);
        }
        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        .notification.success {
            background-color: #10b981;
            color: white;
        }
        .notification.error {
            background-color: #ef4444;
            color: white;
        }
        
        .tab-button.active {
            background-color: #1e40af;
            color: white;
            border-color: #1e40af;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e5e7eb;
        }
    </style>
</head>
<body class="flex min-h-screen">
    <!-- Notifications -->
    <?php if (isset($success_message)): ?>
        <div class="notification show success">
            <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="notification show error">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content flex-1">
        <?php
        $pageTitle = 'Settings';
        $pageSubtitle = 'Manage your account settings';
        require_once 'header.php';
        ?>

        <!-- Page Content -->
        <div class="px-4 py-3">
            <div>
                <!-- Profile Header -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-4">
                    <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6">
                        <div class="relative">
                            <?php
                            $initials = strtoupper($profile['first_name'][0] . $profile['last_name'][0]);
                            $fallback_url = "https://ui-avatars.com/api/?name=" . urlencode($profile['first_name'] . '+' . $profile['last_name']) . "&background=3B82F6&color=ffffff&size=120";
                            $profile_picture = !empty($profile['profile_picture']) ? htmlspecialchars($profile['profile_picture']) : $fallback_url;
                            ?>
                            <img 
                                src="<?= $profile_picture ?>" 
                                alt="Profile Picture" 
                                class="profile-avatar"
                                onerror="this.src='<?= $fallback_url ?>'">
                        </div>
                        <div class="text-center md:text-left">
                            <h2 class="text-2xl font-bold text-nsknavy"><?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></h2>
                            <p class="text-gray-600"><?= htmlspecialchars($profile['specialization'] ?: 'Teacher') ?></p>
                            <p class="text-sm text-gray-500">Teacher ID: <?= htmlspecialchars($profile['teacher_id']) ?></p>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($profile['email']) ?></p>
                        </div>
                    </div>
                </div>

                    <!-- Tab Navigation (Removed - Only Account Settings) -->
                    <div class="border-b border-gray-200 bg-nsknavy">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-lock mr-2"></i>Account Settings
                            </h3>
                            <p class="text-xs text-blue-100 mt-1">Update your email address and password</p>
                        </div>
                    </div>

                    <!-- Account Settings Form -->
                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_account">
                            <input type="hidden" name="current_email" value="<?= htmlspecialchars($profile['email']) ?>">
                            <?= CSRF::getTokenField() ?>

                            <!-- Email Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <div>
                                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent" 
                                           placeholder="your.email@example.com"
                                           required>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle"></i> 
                                    Your current email address is: <strong><?= htmlspecialchars($profile['email']) ?></strong>
                                </p>
                            </div>

                            <!-- Password Section -->
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-semibold text-nsknavy mb-4">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                        <div class="relative">
                                            <input type="password" name="current_password" id="current_password"
                                                   class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent"
                                                   placeholder="Enter current password">
                                            <button type="button" onclick="togglePasswordVisibility('current_password')" 
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                                <i class="fas fa-eye" id="current_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                        <div class="relative">
                                            <input type="password" name="new_password" id="new_password"
                                                   class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent"
                                                   placeholder="Enter new password">
                                            <button type="button" onclick="togglePasswordVisibility('new_password')" 
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                                <i class="fas fa-eye" id="new_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-3">
                                    <i class="fas fa-lightbulb text-yellow-500"></i> 
                                    Leave password fields blank if you don't want to change your password
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-4 border-t">
                                <button type="submit" class="bg-nskgreen hover:bg-green-600 text-white px-8 py-3 rounded-md transition font-medium">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Password visibility toggle function
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '_icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mobileOverlay = document.getElementById('mobileOverlay');

            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-show');
                    if (mobileOverlay) {
                        mobileOverlay.classList.toggle('active');
                    }
                });
            }

            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-show');
                    this.classList.remove('active');
                });
            }

            // Auto-hide notifications
            setTimeout(() => {
                document.querySelectorAll('.notification').forEach(notification => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                });
            }, 5000);
        });
    </script>
</body>
</html>