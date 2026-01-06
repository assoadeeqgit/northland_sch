<?php
/**
 * AJAX Content Helper
 * 
 * This helper determines if a request is an AJAX request
 * and should return only content (not full HTML with sidebar/header)
 * 
 * Usage:
 * 1. Include this file at the top of your page (after auth-check.php)
 * 2. Use isAjaxRequest() to check if request is AJAX
 * 3. Wrap full HTML structure in if (!isAjaxRequest())
 * 4. Wrap content in <div id="main-content">
 */

/**
 * Check if current request is an AJAX request
 * 
 * @return bool True if AJAX request, false otherwise
 */
function isAjaxRequest() {
    return (
        // Check for XMLHttpRequest header
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        // Check for custom AJAX navigation header
        (isset($_SERVER['HTTP_X_AJAX_NAVIGATION']) && 
         $_SERVER['HTTP_X_AJAX_NAVIGATION'] === '1')
    );
}

/**
 * Start output buffering for AJAX content
 * Call this before any HTML output
 */
function startAjaxContent() {
    if (isAjaxRequest()) {
        ob_start();
    }
}

/**
 * End output buffering and send only content for AJAX
 * Call this at the end of your page
 */
function endAjaxContent() {
    if (isAjaxRequest()) {
        $content = ob_get_clean();
        
        // Extract only the content within #main-content div
        // This is a simple regex approach - for production you might want to use DOMDocument
        if (preg_match('/<div[^>]*id=["\']main-content["\'][^>]*>(.*)<\/div>\s*$/s', $content, $matches)) {
            echo $matches[1];
        } else {
            // If no main-content div found, return everything
            echo $content;
        }
        exit;
    }
}

/**
 * Helper to prevent auth redirects in AJAX requests
 * This is already handled in auth-check.php, but this is a reminder
 */
function preventAjaxAuthRedirect() {
    // This is handled in auth-check.php
    // When auth fails in AJAX, it returns 401 JSON instead of redirect
    return isAjaxRequest();
}
