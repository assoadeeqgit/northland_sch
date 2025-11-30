<?php
session_start();
header('Content-Type: application/json');

// Include database configuration
require_once 'config/database.php';

class AuthController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        try {
            switch ($action) {
                case 'login':
                    $this->login($input);
                    break;
                case 'register':
                    $this->register($input);
                    break;
                case 'check_auth':
                    $this->checkAuth($input);
                    break;
                case 'verify_token':
                    $this->verifyToken($input);
                    break;
                case 'logout':
                    $this->logout();
                    break;
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    private function login($data)
    {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required');
        }

        // Get user with their role, regardless of active status
        $stmt = $this->db->prepare("
            SELECT u.*, ur.role_name, ur.permissions 
            FROM users u 
            LEFT JOIN user_roles ur ON u.user_type = ur.role_name
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Step 1: Check if user exists at all
        if (!$user) {
            $this->logAuthEvent(null, 'failed_login', $_SERVER['REMOTE_ADDR'], 'User not found');
            throw new Exception('Invalid email or password');
        }

        // Step 2: Check if account is locked
        if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
            $this->logAuthEvent($user['id'], 'failed_login', $_SERVER['REMOTE_ADDR'], 'Account locked');
            throw new Exception('Account is temporarily locked. Please try again later.');
        }

        // Step 3: Check password
        if (!password_verify($password, $user['password_hash'])) {
            $this->logAuthEvent($user['id'], 'failed_login', $_SERVER['REMOTE_ADDR'], 'Incorrect password');
            throw new Exception('Invalid email or password');
        }

        // Step 4: Password is correct, NOW check if active
        if ($user['is_active'] == 0) {
            $this->logAuthEvent($user['id'], 'failed_login', $_SERVER['REMOTE_ADDR'], 'Account inactive');
            throw new Exception('Your account is not active. Please contact an administrator.');
        }

        // --- All checks passed, user is valid and active ---

        // Reset login attempts on successful login
        $this->resetLoginAttempts($user['id']);

        // Update last login
        $this->updateLastLogin($user['id']);

        // Create session token
        $sessionToken = $this->createUserSession($user);

        // Set PHP session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['session_token'] = $sessionToken;

        // Log successful login
        $this->logAuthEvent($user['id'], 'login', $_SERVER['REMOTE_ADDR']);

        // Prepare user data for response
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role_name' => $user['role_name'],
            'permissions' => json_decode($user['permissions'] ?? '[]', true)
        ];

        $this->sendResponse(true, 'Login successful', [
            'user' => $userData,
            'session_token' => $sessionToken
        ]);
    }

    private function register($data)
    {
        $userData = $data['data'] ?? [];

        // Validate required fields
        $required = ['user_type', 'first_name', 'last_name', 'email', 'phone', 'password'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Check if email already exists
        if ($this->emailExists($userData['email'])) {
            throw new Exception('Email already registered');
        }

        // Generate username
        $username = $this->generateUsername($userData['first_name'], $userData['last_name']);

        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Insert into users table
            $stmt = $this->db->prepare("
                INSERT INTO users 
                (username, email, password_hash, user_type, first_name, last_name, phone, registration_step, email_verified)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)
            ");

            $stmt->execute([
                $username,
                $userData['email'],
                $passwordHash,
                $userData['user_type'],
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone']
            ]);

            $userId = $this->db->lastInsertId();

            // Create role-specific profile
            $this->createUserProfile($userId, $userData);

            // Commit transaction
            $this->db->commit();

            // Log registration
            $this->logAuthEvent($userId, 'registration', $_SERVER['REMOTE_ADDR']);

            $this->sendResponse(true, 'Registration successful. Please check your email for verification.');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Registration failed: ' . $e->getMessage());
        }
    }

    private function createUserProfile($userId, $userData)
    {
        $userType = $userData['user_type'];

        switch ($userType) {
            case 'student':
                $this->createStudentProfile($userId, $userData);
                break;
            case 'teacher':
                $this->createTeacherProfile($userId, $userData);
                break;
            case 'staff':
                $this->createStaffProfile($userId, $userData);
                break;
            case 'admin':
                $this->createAdminProfile($userId, $userData);
                break;
        }
    }

    private function createStudentProfile($userId, $userData)
    {
        $stmt = $this->db->prepare("
            INSERT INTO student_profiles 
            (user_id, date_of_birth, gender, class_level, parent_name, parent_phone, previous_school)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $userData['dateOfBirth'] ?? null,
            $userData['gender'] ?? null,
            $userData['classLevel'] ?? null,
            $userData['parentName'] ?? null,
            $userData['parentPhone'] ?? null,
            $userData['previousSchool'] ?? null
        ]);
    }

    private function createTeacherProfile($userId, $userData)
    {
        // Generate teacher ID
        $teacherId = 'TCH' . str_pad($userId, 3, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO teacher_profiles 
            (user_id, qualification, subject_specialization, years_experience, department, employment_type, teacher_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $userData['qualification'] ?? null,
            $userData['subjects'] ?? null,
            $userData['experience'] ?? null,
            $userData['department'] ?? null,
            $userData['employmentType'] ?? null,
            $teacherId
        ]);

        // Also insert into teachers table
        $stmt = $this->db->prepare("
            INSERT INTO teachers (user_id, teacher_id, qualification, specialization, employment_date)
            VALUES (?, ?, ?, ?, CURDATE())
        ");

        $stmt->execute([
            $userId,
            $teacherId,
            $userData['qualification'] ?? null,
            $userData['subjects'] ?? null
        ]);
    }

    private function createStaffProfile($userId, $userData)
    {
        $staffId = 'STF' . str_pad($userId, 3, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO staff_profiles 
            (user_id, department, position, employment_type, supervisor, staff_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $userData['department'] ?? null,
            $userData['position'] ?? null,
            $userData['employmentType'] ?? null,
            $userData['supervisor'] ?? null,
            $staffId
        ]);
    }

    private function createAdminProfile($userId, $userData)
    {
        $adminId = 'ADM' . str_pad($userId, 3, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO admin_profiles 
            (user_id, admin_level, department_access, special_permissions, admin_id)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $userData['adminLevel'] ?? null,
            $userData['department'] ?? null,
            $userData['permissions'] ?? null,
            $adminId
        ]);
    }

    private function emailExists($email)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    private function generateUsername($firstName, $lastName)
    {
        $baseUsername = strtolower($firstName . '.' . $lastName);
        $username = $baseUsername;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    private function usernameExists($username)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }

    private function createUserSession($user)
    {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->db->prepare("
            INSERT INTO user_sessions 
            (user_id, session_token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);

        return $sessionToken;
    }

    private function updateLastLogin($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW(), last_activity = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }

    private function resetLoginAttempts($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, account_locked_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    private function logAuthEvent($userId, $eventType, $ipAddress, $details = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO auth_audit_log (user_id, event_type, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?)
        ");

        $detailsArray = ['timestamp' => date('Y-m-d H:i:s')];
        if ($details) {
            $detailsArray['reason'] = $details;
        }

        $stmt->execute([
            $userId,
            $eventType,
            $ipAddress,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($detailsArray)
        ]);
    }

    private function checkAuth($data)
    {
        $token = $data['token'] ?? '';

        if (empty($token)) {
            throw new Exception('No token provided');
        }

        $stmt = $this->db->prepare("
            SELECT us.*, u.* FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
        ");

        $stmt->execute([$token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            throw new Exception('Invalid or expired session');
        }

        // Update last activity
        $this->updateLastLogin($session['user_id']);

        $this->sendResponse(true, 'Authenticated', [
            'user' => [
                'id' => $session['id'],
                'username' => $session['username'],
                'email' => $session['email'],
                'user_type' => $session['user_type'],
                'first_name' => $session['first_name'],
                'last_name' => $session['last_name']
            ]
        ]);
    }

    private function verifyToken($data)
    {
        $token = $data['token'] ?? '';
        
        if (empty($token)) {
            throw new Exception('No token provided');
        }
        
        $stmt = $this->db->prepare("
            SELECT us.*, u.* FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
        ");
        
        $stmt->execute([$token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            throw new Exception('Invalid or expired session');
        }
        
        // Set session variables
        $_SESSION['user_id'] = $session['user_id'];
        $_SESSION['user_type'] = $session['user_type'];
        $_SESSION['user_name'] = $session['first_name'] . ' ' . $session['last_name'];
        $_SESSION['email'] = $session['email'];
        $_SESSION['session_token'] = $token;
        
        $this->sendResponse(true, 'Token valid', [
            'user' => [
                'id' => $session['user_id'],
                'user_type' => $session['user_type'],
                'first_name' => $session['first_name'],
                'last_name' => $session['last_name'],
                'email' => $session['email']
            ]
        ]);
    }

    private function logout()
    {
        $token = $_POST['token'] ?? '';

        if (!empty($token)) {
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$token]);
        }

        session_destroy();
        $this->sendResponse(true, 'Logged out successfully');
    }

    private function sendResponse($success, $message, $data = [])
    {
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// Handle the request
$auth = new AuthController();
$auth->handleRequest();
?>