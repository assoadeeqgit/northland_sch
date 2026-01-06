<?php
/**
 * Global Term Helper - Include this in all dashboard pages
 * Ensures consistent term usage across the entire system
 */

// Auto-include TermSync if not already loaded
if (!class_exists('TermSync')) {
    require_once __DIR__ . '/TermSync.php';
}

// Initialize global term sync
$GLOBALS['termSync'] = TermSync::getInstance();

// Global variables for current term and session
$GLOBALS['current_term'] = $GLOBALS['termSync']->getCurrentTerm();
$GLOBALS['current_session'] = $GLOBALS['termSync']->getCurrentSession();

// Helper functions for easy access
function getCurrentTermId() {
    return $GLOBALS['current_term']['id'] ?? null;
}

function getCurrentSessionId() {
    return $GLOBALS['current_session']['id'] ?? null;
}

function getCurrentTermName() {
    return $GLOBALS['current_term']['term_name'] ?? 'No Active Term';
}

function getCurrentSessionName() {
    return $GLOBALS['current_session']['session_name'] ?? 'No Active Session';
}

// Database query helper with automatic term filtering
function getTermAwareQuery($baseQuery, $tableAlias = '') {
    $conditions = getTermConditions($tableAlias);
    
    if ($conditions['where_clause'] !== '1=1') {
        if (stripos($baseQuery, 'WHERE') !== false) {
            $baseQuery .= ' AND ' . $conditions['where_clause'];
        } else {
            $baseQuery .= ' WHERE ' . $conditions['where_clause'];
        }
    }
    
    return $baseQuery;
}

// Validate and auto-fix term consistency on every page load
$consistencyIssues = $GLOBALS['termSync']->validateConsistency();
if (!empty($consistencyIssues)) {
    error_log("Term Consistency Issues: " . implode(', ', $consistencyIssues));
    
    // Auto-fix if possible
    if ($GLOBALS['termSync']->autoFixConsistency()) {
        // Reload term data after fix
        $GLOBALS['current_term'] = $GLOBALS['termSync']->getCurrentTerm();
        $GLOBALS['current_session'] = $GLOBALS['termSync']->getCurrentSession();
    }
}

// Sync session variables
$GLOBALS['termSync']->syncAllModules();

// JavaScript helper for frontend
if (!defined('API_MODE')): /* HTML/JS Output ONLY if not in API Mode */
?>
<script>
// Global term data for JavaScript
window.currentTerm = {
    id: <?= json_encode(getCurrentTermId()) ?>,
    name: <?= json_encode(getCurrentTermName()) ?>,
    session_id: <?= json_encode(getCurrentSessionId()) ?>,
    session_name: <?= json_encode(getCurrentSessionName()) ?>
};

// Function to check if term has changed and reload if needed
function checkTermSync() {
    fetch('<?= $_SERVER['PHP_SELF'] ?>?check_term=1')
        .then(response => response.json())
        .then(data => {
            if (data.term_id !== window.currentTerm.id) {
                // Term has changed, reload page
                window.location.reload();
            }
        })
        .catch(error => console.warn('Term sync check failed:', error));
}

// Check term sync every 30 seconds
setInterval(checkTermSync, 30000);
</script>
<?php
endif; // End API_MODE check

// Handle AJAX term sync check
if (isset($_GET['check_term'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'term_id' => getCurrentTermId(),
        'session_id' => getCurrentSessionId()
    ]);
    exit;
}
?>
