<?php
/**
 * Teacher Settings Page
 * Allows teachers to update their bank information and account settings
 */

// Start session and check authentication
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php';

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
     * Update teacher's bank information
     */
    public function updateBankInfo(array $data): bool
    {
        try {
            $query = "
                UPDATE teachers SET 
                    bank_name = :bank_name,
                    account_number = :account_number
                WHERE user_id = :user_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':bank_name' => $data['bank_name'],
                ':account_number' => $data['account_number'],
                ':user_id' => $this->teacher_user_id
            ]);

            $this->success_message = "Bank information updated successfully!";
            return true;
        } catch (PDOException $e) {
            $this->error_message = "Error updating bank information: " . $e->getMessage();
            error_log("Update bank info error: " . $e->getMessage());
            return false;
        }
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
            if (!empty($data['email']) && $data['email'] !== $data['current_email']) {
                // Check if email already exists
                $email_check = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                $stmt_check = $this->conn->prepare($email_check);
                $stmt_check->execute([
                    ':email' => $data['email'],
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
                    ':email' => $data['email'],
                    ':user_id' => $this->teacher_user_id
                ]);
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
            case 'update_bank':
                $is_success = $teacher_settings->updateBankInfo($_POST);
                break;

            case 'update_account':
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
        header("Location: login-form.php?error=auth");
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
        <!-- Desktop Header -->
        <header class="desktop-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Settings</h1>
                </div>
                <div class="hidden md:flex items-center space-x-2">
                    <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-nsknavy"><?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></p>
                        <p class="text-xs text-gray-600"><?= htmlspecialchars($profile['specialization'] ?: 'Teacher') ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Header -->
        <header class="mobile-header bg-white shadow-md p-4 md:hidden">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">Settings</h1>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                        <?= strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-4 md:p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Profile Header -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
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

                <!-- Settings Tabs -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200">
                        <nav class="flex overflow-x-auto">
                            <button class="tab-button px-6 py-4 font-medium text-sm border-b-2 border-transparent hover:bg-gray-50 active" data-tab="personal">
                                <i class="fas fa-user mr-2"></i>Personal Info
                            </button>
                            <button class="tab-button px-6 py-4 font-medium text-sm border-b-2 border-transparent hover:bg-gray-50" data-tab="bank">
                                <i class="fas fa-university mr-2"></i>Bank Information
                            </button>
                            <button class="tab-button px-6 py-4 font-medium text-sm border-b-2 border-transparent hover:bg-gray-50" data-tab="account">
                                <i class="fas fa-lock mr-2"></i>Account Settings
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Personal Information Tab -->
                        <div id="personal-tab" class="tab-content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['first_name']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['last_name']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['phone']) ?>" class="w-full px-3py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['date_of_birth']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['gender']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Teacher ID</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['teacher_id']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" rows="3" readonly><?= htmlspecialchars($profile['address']) ?></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Qualification</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['qualification']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['specialization']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employment Date</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['employment_date']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                                    <input type="text" value="<?= htmlspecialchars($profile['employment_type']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Information Tab -->
                        <div id="bank-tab" class="tab-content hidden">
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="update_bank">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                                        <input type="text" name="bank_name" value="<?= htmlspecialchars($profile['bank_name']) ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent"
                                               placeholder="Enter bank name">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                                        <input type="text" name="account_number" value="<?= htmlspecialchars($profile['account_number']) ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent"
                                               placeholder="Enter account number">
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-nskgreen text-white px-6 py-2 rounded-md hover:bg-green-600 transition">
                                        Save Bank Information
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Account Settings Tab -->
                        <div id="account-tab" class="tab-content hidden">
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="update_account">
                                <input type="hidden" name="current_email" value="<?= htmlspecialchars($profile['email']) ?>">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                        <input type="text" value="<?= htmlspecialchars($profile['username']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                                        <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent" 
                                               required>
                                    </div>
                                </div>

                                <div class="border-t pt-6">
                                    <h3 class="text-lg font-semibold text-nsknavy mb-4">Change Password</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                            <input type="password" name="current_password" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                            <input type="password" name="new_password" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-nskblue focus:border-transparent">
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">Leave password fields blank if you don't want to change your password</p>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-nskgreen text-white px-6 py-2 rounded-md hover:bg-green-600 transition">
                                        Update Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');

                    // Update active tab button
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active', 'border-nskblue', 'text-white', 'bg-nskblue');
                        btn.classList.add('border-transparent', 'text-gray-600');
                    });
                    this.classList.add('active', 'border-nskblue', 'text-white', 'bg-nskblue');
                    this.classList.remove('border-transparent', 'text-gray-600');

                    // Show active tab content
                    tabContents.forEach(content => content.classList.add('hidden'));
                    document.getElementById(`${tabId}-tab`).classList.remove('hidden');
                });
            });

            // Mobile menu functionality
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