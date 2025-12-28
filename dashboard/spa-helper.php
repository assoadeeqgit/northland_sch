<?php
/**
 * Content Wrapper Helper
 * Determines whether to serve full page or content-only for AJAX requests
 * 
 * Usage: Place at the top of each page file
 * <?php $isAjax = isAjaxRequest(); ?>
 * <?php if (!$isAjax) include 'layout-header.php'; ?>
 * ... your content here ...
 * <?php if (!$isAjax) include 'layout-footer.php'; ?>
 */

/**
 * Check if request is AJAX
 * @return bool
 */
function isAjaxRequest() {
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) || isset($_GET['ajax']);
}

/**
 * Start content capture for AJAX requests
 */
function startContentCapture() {
    if (isAjaxRequest()) {
        ob_start();
    }
}

/**
 * End content capture and send for AJAX requests
 */
function endContentCapture() {
    if (isAjaxRequest()) {
        $content = ob_get_clean();
        // Send content-only for AJAX
        echo $content;
        exit;
    }
}

/**
 * Check if we should include layout wrappers
 * @return bool
 */
function shouldIncludeLayout() {
    return !isAjaxRequest();
}

// Set headers for AJAX responses
if (isAjaxRequest()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type: ajax-content');
}
