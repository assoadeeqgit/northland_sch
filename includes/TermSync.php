<?php
/**
 * Term Synchronization System
 * Ensures all parts of the dashboard use the active term consistently
 */

class TermSync {
    private $db;
    private static $instance = null;
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public static function getInstance($database = null) {
        if (self::$instance === null) {
            self::$instance = new self($database);
        }
        return self::$instance;
    }
    
    /**
     * Get current active term
     */
    public function getCurrentTerm() {
        try {
            $stmt = $this->db->query("SELECT * FROM terms WHERE is_current = 1 LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("TermSync Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get current active session
     */
    public function getCurrentSession() {
        try {
            $stmt = $this->db->query("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("TermSync Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Switch to a new term and ensure synchronization
     */
    public function switchTerm($termId) {
        try {
            $this->db->beginTransaction();
            
            // Deactivate all terms
            $this->db->exec("UPDATE terms SET is_current = 0");
            
            // Activate the selected term
            $stmt = $this->db->prepare("UPDATE terms SET is_current = 1 WHERE id = ?");
            $stmt->execute([$termId]);
            
            // Log the change
            $this->logTermChange($termId);
            
            // Trigger synchronization hooks
            $this->triggerSyncHooks($termId);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("TermSync Switch Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure data consistency across all modules
     */
    public function syncAllModules() {
        $currentTerm = $this->getCurrentTerm();
        $currentSession = $this->getCurrentSession();
        
        if (!$currentTerm || !$currentSession) {
            return false;
        }
        
        // Update session variables if they exist
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['current_term_id'] = $currentTerm['id'];
            $_SESSION['current_session_id'] = $currentSession['id'];
        }
        
        return true;
    }
    
    /**
     * Get term-aware query conditions
     */
    public function getTermConditions($tableAlias = '') {
        $currentTerm = $this->getCurrentTerm();
        $currentSession = $this->getCurrentSession();
        
        $prefix = $tableAlias ? $tableAlias . '.' : '';
        
        return [
            'term_id' => $currentTerm['id'] ?? null,
            'session_id' => $currentSession['id'] ?? null,
            'where_clause' => $currentTerm && $currentSession ? 
                "{$prefix}term_id = {$currentTerm['id']} AND {$prefix}academic_session_id = {$currentSession['id']}" : 
                "1=1"
        ];
    }
    
    /**
     * Log term changes for audit
     */
    private function logTermChange($termId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, description, created_at) 
                VALUES (?, 'term_switch', ?, NOW())
            ");
            
            $userId = $_SESSION['user_id'] ?? 0;
            $description = "Switched to term ID: $termId";
            
            $stmt->execute([$userId, $description]);
        } catch (Exception $e) {
            error_log("TermSync Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Trigger synchronization hooks for other modules
     */
    private function triggerSyncHooks($termId) {
        // Clear any cached term data
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
        
        // Update any term-dependent configurations
        $this->updateTermDependentData($termId);
    }
    
    /**
     * Update term-dependent data
     */
    private function updateTermDependentData($termId) {
        try {
            // Update any cached or derived data that depends on the current term
            // This can be extended based on specific needs
            
            // Example: Update fee structures for the new term
            // $this->syncFeeStructures($termId);
            
        } catch (Exception $e) {
            error_log("TermSync Data Update Error: " . $e->getMessage());
        }
    }
    
    /**
     * Validate term consistency across the system
     */
    public function validateConsistency() {
        $issues = [];
        
        try {
            // Check for multiple active terms
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM terms WHERE is_current = 1");
            $activeTerms = $stmt->fetchColumn();
            
            if ($activeTerms > 1) {
                $issues[] = "Multiple active terms detected";
            } elseif ($activeTerms == 0) {
                $issues[] = "No active term found";
            }
            
            // Check for multiple active sessions
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM academic_sessions WHERE is_current = 1");
            $activeSessions = $stmt->fetchColumn();
            
            if ($activeSessions > 1) {
                $issues[] = "Multiple active sessions detected";
            } elseif ($activeSessions == 0) {
                $issues[] = "No active session found";
            }
            
        } catch (Exception $e) {
            $issues[] = "Database error: " . $e->getMessage();
        }
        
        return $issues;
    }
    
    /**
     * Auto-fix consistency issues
     */
    public function autoFixConsistency() {
        try {
            $this->db->beginTransaction();
            
            // Fix multiple active terms - keep the latest one
            $this->db->exec("UPDATE terms SET is_current = 0");
            $this->db->exec("UPDATE terms SET is_current = 1 WHERE id = (SELECT id FROM (SELECT id FROM terms ORDER BY id DESC LIMIT 1) as t)");
            
            // Fix multiple active sessions - keep the latest one
            $this->db->exec("UPDATE academic_sessions SET is_current = 0");
            $this->db->exec("UPDATE academic_sessions SET is_current = 1 WHERE id = (SELECT id FROM (SELECT id FROM academic_sessions ORDER BY id DESC LIMIT 1) as s)");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("TermSync AutoFix Error: " . $e->getMessage());
            return false;
        }
    }
}

// Global helper functions
function getCurrentTerm() {
    return TermSync::getInstance()->getCurrentTerm();
}

function getCurrentSession() {
    return TermSync::getInstance()->getCurrentSession();
}

function getTermConditions($tableAlias = '') {
    return TermSync::getInstance()->getTermConditions($tableAlias);
}

function switchTerm($termId) {
    return TermSync::getInstance()->switchTerm($termId);
}
?>
