<?php
// --- ADD THIS LINE ---
date_default_timezone_set('Africa/Lagos'); // (Lagos is the correct identifier for WAT)
// --- END ---

/**
 * Logs an activity to the activity_log table.
 *
 * @param PDO $db The database connection.
 * @param string $user_name The name of the user performing the action.
 * @param string $action_type A short title for the action (e.g., "New Student").
 * @param string $description A full description (e.g., "Added student: John Doe (STU1234)").
 * @param string $icon The Font Awesome icon class (e.g., "fas fa-user-plus").
 * @param string $color The Tailwind background color class (e.g., "bg-nsklightblue").
 */
function logActivity($db, $user_name, $action_type, $description, $icon, $color) {
    try {
        $sql = "INSERT INTO activity_log (user_name, action_type, description, icon, color) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_name, $action_type, $description, $icon, $color]);
        
        // Optional: Trim the log to keep it from getting too big
        $db->query("DELETE FROM activity_log WHERE id NOT IN (SELECT id FROM (SELECT id FROM activity_log ORDER BY id DESC LIMIT 100) as temp)");
        
    } catch (Exception $e) {
        // Fail silently so it doesn't break the main action
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Creates a "time ago" string from a timestamp.
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime; // This will now use 'Africa/Lagos'
    $ago = new DateTime($datetime); // This will also assume 'Africa/Lagos'
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d - ($w * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    $diff_values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $w,
        'd' => $d,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];

    foreach ($string as $k => &$v) {
        if ($diff_values[$k]) {
            $v = $diff_values[$k] . ' ' . $v . ($diff_values[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>