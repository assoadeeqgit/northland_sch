<?php
require_once 'auth-check.php';
checkAuth('admin');
require_once '../config/database.php';
require_once '../config/logger.php';

// Get database credentials
$database = new Database();
$db = $database->getConnection();

// Database credentials from config
$db_host = '127.0.0.1';
$db_name = 'northland_schools_kano';
$db_user = 'root';
$db_pass = '309612.Aa';

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Create backups directory if it doesn't exist
$backup_dir = dirname(__DIR__) . '/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// ===== BACKUP DATABASE =====
if (isset($_POST['backup_database'])) {
    try {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = $backup_dir . $backup_file;
        $error_log = $backup_dir . 'backup_error.log';
        
        // Use mysqldump command - redirect stderr to separate error log
        // Use mysqldump command - redirect stderr to separate error log
        $command = sprintf(
            '/usr/bin/mysqldump -h %s -u %s -p%s %s 2> %s > %s',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            escapeshellarg($db_pass),
            escapeshellarg($db_name),
            escapeshellarg($error_log),
            escapeshellarg($backup_path)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && file_exists($backup_path) && filesize($backup_path) > 0) {
            // Clean up error log if backup was successful
            if (file_exists($error_log)) {
                unlink($error_log);
            }
            
            // Log activity
            logActivity(
                $db,
                $admin_name,
                "Database Backup",
                "$admin_name created a database backup: $backup_file",
                "fas fa-database",
                "bg-nskblue"
            );
            
            $_SESSION['success'] = "Database backup created successfully: $backup_file";
        } else {
            $error_details = "Unknown error";
            if (file_exists($error_log)) {
                $error_details = file_get_contents($error_log);
                unlink($error_log); // Clean up
            }
            throw new Exception("Backup failed. Details: " . $error_details);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Backup error: " . $e->getMessage();
    }
    
    header("Location: settings.php");
    exit();
}

// ===== RESTORE DATABASE =====
if (isset($_POST['restore_database'])) {
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a valid backup file.");
        }
        
        $uploaded_file = $_FILES['backup_file'];
        $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
        
        if ($file_extension !== 'sql') {
            throw new Exception("Invalid file type. Only .sql files are allowed.");
        }
        
        $temp_path = $uploaded_file['tmp_name'];
        $error_log = $backup_dir . 'restore_error.log';
        
        // Use mysql command to restore - redirect stderr to error log
        // Use mysql command to restore - redirect stderr to error log
        $command = sprintf(
            '/usr/bin/mysql -h %s -u %s -p%s %s 2> %s < %s',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            escapeshellarg($db_pass),
            escapeshellarg($db_name),
            escapeshellarg($error_log),
            escapeshellarg($temp_path)
        );
        
        exec($command, $output, $return_var);
        
        // Check if there were any actual errors (ignore warnings)
        $had_errors = false;
        if (file_exists($error_log)) {
            $error_content = file_get_contents($error_log);
            // Check for actual ERROR messages, not just warnings
            if (preg_match('/ERROR \d+/', $error_content)) {
                $had_errors = true;
                throw new Exception("Restore failed. " . $error_content);
            }
            // Clean up error log if no actual errors
            unlink($error_log);
        }
        
        if ($return_var === 0) {
            // Reconnect to log the activity with restored database
            $database = new Database();
            $db = $database->getConnection();
            
            logActivity(
                $db,
                $admin_name,
                "Database Restore",
                "$admin_name restored database from backup: {$uploaded_file['name']}",
                "fas fa-upload",
                "bg-nskgreen"
            );
            
            $_SESSION['success'] = "Database restored successfully from: {$uploaded_file['name']}";
        } else {
            throw new Exception("Restore failed. Error: " . implode("\n", $output));
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Restore error: " . $e->getMessage();
    }
    
    header("Location: settings.php");
    exit();
}

// ===== CLEAR DATABASE =====
if (isset($_POST['clear_database'])) {
    try {
        // Verify confirmation
        if (!isset($_POST['confirm_clear']) || $_POST['confirm_clear'] !== 'CLEAR') {
            throw new Exception("Please type 'CLEAR' to confirm database clearing.");
        }
        
        // Create automatic backup before clearing
        $backup_file = 'pre_clear_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = $backup_dir . $backup_file;
        $error_log = $backup_dir . 'clear_backup_error.log';
        
        $command = sprintf(
            '/usr/bin/mysqldump -h %s -u %s -p%s %s 2> %s > %s',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            escapeshellarg($db_pass),
            escapeshellarg($db_name),
            escapeshellarg($error_log),
            escapeshellarg($backup_path)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0 || !file_exists($backup_path) || filesize($backup_path) === 0) {
            throw new Exception("Automatic backup failed. Aborting clear operation.");
        }
        
        // Clean up error log
        if (file_exists($error_log)) {
            unlink($error_log);
        }
        
        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Disable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Tables to preserve (configuration and authentication data)
        $preserve_tables = [
            'users',                    // User accounts
            'settings',                 // System settings
            'terms',                    // Academic terms
            'academic_sessions',        // Academic sessions/years
            'classes',                  // Class structure
            'admin_profiles',           // Admin profile data
            'user_sessions'             // Active user sessions
        ];
        
        // Clear all tables except preserved ones
        foreach ($tables as $table) {
            if (!in_array($table, $preserve_tables)) {
                $db->exec("TRUNCATE TABLE `$table`");
            }
        }
        
        // Remove all non-admin users (Teachers, Students, Parents)
        // This ensures the users table is cleaned up while preserving admin access
        $db->exec("DELETE FROM users WHERE user_type NOT IN ('admin', 'administrator', 'super_admin')");
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Log activity
        logActivity(
            $db,
            $admin_name,
            "Database Cleared",
            "$admin_name cleared all database data (backup saved: $backup_file)",
            "fas fa-trash-alt",
            "bg-nskred"
        );
        
        $_SESSION['success'] = "Database cleared successfully. Automatic backup created: $backup_file";
    } catch (Exception $e) {
        $_SESSION['error'] = "Clear error: " . $e->getMessage();
    }
    
    header("Location: settings.php");
    exit();
}

// ===== DOWNLOAD BACKUP =====
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = $backup_dir . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    } else {
        $_SESSION['error'] = "Backup file not found.";
        header("Location: settings.php");
        exit();
    }
}

// ===== DELETE BACKUP =====
if (isset($_POST['delete_backup'])) {
    try {
        $filename = basename($_POST['backup_filename']);
        $filepath = $backup_dir . $filename;
        
        if (file_exists($filepath)) {
            unlink($filepath);
            
            logActivity(
                $db,
                $admin_name,
                "Backup Deleted",
                "$admin_name deleted backup file: $filename",
                "fas fa-trash",
                "bg-nskred"
            );
            
            $_SESSION['success'] = "Backup file deleted successfully.";
        } else {
            throw new Exception("Backup file not found.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Delete error: " . $e->getMessage();
    }
    
    header("Location: settings.php");
    exit();
}

// If no action was taken, redirect back
header("Location: settings.php");
exit();
