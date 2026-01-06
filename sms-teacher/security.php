<?php
/**
 * CSRF Protection Utilities
 * Provides token generation and validation for form security
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class CSRF {
    /**
     * Generate a CSRF token and store it in session
     * @return string The generated token
     */
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Get the current CSRF token
     * @return string|null The current token or null if not set
     */
    public static function getToken() {
        return $_SESSION['csrf_token'] ?? null;
    }
    
    /**
     * Validate a CSRF token
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate HTML input field for CSRF token
     * @return string HTML input field
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Validate CSRF token from POST request and exit if invalid
     * @param bool $ajax Whether this is an AJAX request (affects response format)
     */
    public static function validateRequest($ajax = false) {
        $token = $_POST['csrf_token'] ?? '';
        
        if (!self::validateToken($token)) {
            error_log("CSRF validation failed for user " . ($_SESSION['user_id'] ?? 'unknown') . " from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            if ($ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Security validation failed. Please refresh the page and try again.']);
            } else {
                http_response_code(403);
                die('Security validation failed. Please refresh the page and try again.');
            }
            exit;
        }
    }
    
    /**
     * Regenerate CSRF token (useful after sensitive operations)
     */
    public static function regenerateToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}

/**
 * Session Security Utilities
 */
class SessionSecurity {
    /**
     * Initialize secure session settings
     */
    public static function init() {
        // Regenerate session ID to prevent fixation attacks
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
        
        // Set session timeout (30 minutes)
        $timeout = 1800;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            session_unset();
            session_destroy();
            header("Location: ../login-form.php?timeout=1");
            exit;
        }
        $_SESSION['last_activity'] = time();
        
        // Browser fingerprint validation
        $fingerprint = self::getBrowserFingerprint();
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $fingerprint;
        } elseif ($_SESSION['fingerprint'] !== $fingerprint) {
            error_log("Session hijacking attempt detected for user " . ($_SESSION['user_id'] ?? 'unknown'));
            session_unset();
            session_destroy();
            header("Location: ../login-form.php?security=1");
            exit;
        }
    }
    
    /**
     * Get browser fingerprint for session validation
     * @return string Browser fingerprint hash
     */
    private static function getBrowserFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }
}

/**
 * Rate Limiting Utilities
 */
class RateLimiter {
    /**
     * Check if action is rate limited
     * @param string $action Action identifier (e.g., 'upload', 'login')
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public static function check($action, $maxAttempts = 5, $timeWindow = 300) {
        $key = 'rate_limit_' . $action . '_' . ($_SESSION['user_id'] ?? session_id());
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
            return true;
        }
        
        $data = $_SESSION[$key];
        $elapsed = time() - $data['start_time'];
        
        // Reset if time window has passed
        if ($elapsed > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxAttempts) {
            $remainingTime = $timeWindow - $elapsed;
            error_log("Rate limit exceeded for action '$action' by user " . ($_SESSION['user_id'] ?? 'unknown'));
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Get remaining time until rate limit resets
     * @param string $action Action identifier
     * @param int $timeWindow Time window in seconds
     * @return int Seconds remaining
     */
    public static function getRemainingTime($action, $timeWindow = 300) {
        $key = 'rate_limit_' . $action . '_' . ($_SESSION['user_id'] ?? session_id());
        
        if (!isset($_SESSION[$key])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION[$key]['start_time'];
        return max(0, $timeWindow - $elapsed);
    }
}
