<?php
// Start session and check authentication
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php';

class ViewResultsData {
    private $conn;
    private $teacher_user_id;
    private $teacher_id;

    public function __construct(Database $database) {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed");
        }
        
        $this->teacher_user_id = $_SESSION['user_id'];
        $this->setTeacherId();
    }
    
    private function setTeacherId() {
        $query = "SELECT id FROM teachers WHERE user_id = :teacher_user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
        $stmt->execute();
        $this->teacher_id = $stmt->fetchColumn();
        
        if (!$this->teacher_id) {
            throw new Exception("Teacher profile not found");
        }
    }
    
    public function getTeacherProfile(): array {
        $profile = [
            'first_name' => 'N/A', 
            'last_name' => 'N/A', 
            'initials' => 'NN', 
            'specialization' => 'N/A',
            'teacher_id' => 'N/A'
        ];
        
        $query = "
            SELECT u.first_name, u.last_name, tp.subject_specialization, t.teacher_id
            FROM users u
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            LEFT JOIN teachers t ON u.id = t.user_id
            WHERE u.id = :teacher_user_id
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
            $stmt->execute();
            $data = $stmt->fetch();

            if ($data) {
                $profile['first_name'] = $data['first_name'];
                $profile['last_name'] = $data['last_name'];
                $profile['initials'] = strtoupper(substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1));
                $profile['specialization'] = $data['subject_specialization'] ?: 'Teacher';
                $profile['teacher_id'] = $data['teacher_id'] ?: 'N/A';
            }
        } catch (PDOException $e) {
            error_log("Error fetching profile: " . $e->getMessage());
        }

        return $profile;
    }
    
    public function getAcademicSessions(): array {
        $query = "SELECT id, session_name, is_current FROM academic_sessions ORDER BY start_date DESC";
        try {
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching academic sessions: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTermsBySession(int $session_id): array {
        $query = "SELECT id, term_name, is_current FROM terms WHERE academic_session_id = :sid ORDER BY start_date DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sid', $session_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching terms: " . $e->getMessage());
            return [];
        }
    }

    public function getTeacherClassesAndSubjects(): array {
        $classes = [];
        if (!$this->teacher_id) return $classes;

        $query = "
            SELECT 
                cs.class_id,
                cs.subject_id,
                c.class_name,
                s.subject_name
            FROM class_subjects cs
            JOIN classes c ON cs.class_id = c.id
            JOIN subjects s ON cs.subject_id = s.id
            WHERE cs.teacher_id = :tid
            ORDER BY c.class_name, s.subject_name
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tid', $this->teacher_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching teacher classes: " . $e->getMessage());
            return [];
        }
    }
    
    public function getResults($session_id, $term_id, $class_id, $subject_id, $student_ids = []): array {
        $query = "
            SELECT 
                sr.*,
                u.first_name, u.last_name,
                s.student_id as admission_number,
                c.class_name,
                sub.subject_name,
                ses.session_name,
                t.term_name
            FROM student_results sr
            JOIN students s ON sr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN classes c ON sr.class_id = c.id
            JOIN subjects sub ON sr.subject_id = sub.id
            JOIN academic_sessions ses ON sr.session_id = ses.id
            JOIN terms t ON sr.term_id = t.id
            WHERE sr.session_id = :session_id 
            AND sr.term_id = :term_id 
            AND sr.class_id = :class_id 
            AND sr.subject_id = :subject_id
        ";
        
        $params = [
            ':session_id' => $session_id,
            ':term_id' => $term_id,
            ':class_id' => $class_id,
            ':subject_id' => $subject_id
        ];
        
        if (!empty($student_ids)) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $query .= " AND sr.student_id IN ($placeholders)";
            $params = array_merge($params, $student_ids);
        }
        
        $query .= " ORDER BY u.last_name, u.first_name";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching results: " . $e->getMessage());
            return [];
        }
    }
    
    public function exportToExcel($results, $filename = 'student_results') {
        // Simple CSV export for now - you can enhance with PhpSpreadsheet later
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Student ID', 'Student Name', 'Class', 'Subject', 'Session', 'Term', 'CA Score', 'Exam Score', 'Total Score', 'Grade']);
        
        // Data
        foreach ($results as $result) {
            fputcsv($output, [
                $result['admission_number'],
                $result['first_name'] . ' ' . $result['last_name'],
                $result['class_name'],
                $result['subject_name'],
                $result['session_name'],
                $result['term_name'],
                $result['ca_score'],
                $result['exam_score'],
                $result['total_score'],
                $result['grade']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    public function exportToPDF($results, $filename = 'student_results') {
        // Output HTML for browser printing (Save as PDF)
        $html = $this->generatePDFHTML($results);
        
        // Add auto-print script and back button
        $html = str_replace('</body>', '
            <script>window.onload = function() { window.print(); }</script>
            <div style="position: fixed; top: 10px; left: 10px; display: print: none;">
                <button onclick="window.history.back()" style="padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer;">&larr; Back</button>
            </div>
        </body>', $html);
        
        header('Content-Type: text/html');
        // Remove attachment header so it opens in browser
        // header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        echo $html;
        exit;
    }
    
    private function generatePDFHTML($results): string {
        // Read logo and convert to base64
        $logo_path = __DIR__ . '/school_logo.png';
        $logo_data = '';
        if (file_exists($logo_path)) {
            $type = pathinfo($logo_path, PATHINFO_EXTENSION);
            $data = file_get_contents($logo_path);
            $logo_data = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Northland Schools Kano</title>
            <style>
                body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; color: #333; }
                .report-container { max-width: 1000px; margin: 0 auto; }
                
                /* School Colors */
                :root {
                    --nsk-blue: #1e40af;
                    --nsk-gold: #f59e0b;
                }
                
                @page {
                    size: A4;
                    margin: 1cm;
                }
                
                @media print {
                    @page {
                        margin: 0.5cm;
                    }
                    body { 
                        padding: 0; 
                        -webkit-print-color-adjust: exact; 
                    }
                    .no-print { display: none; }
                    th { background-color: #1e40af !important; color: white !important; }
                    
                    /* Hide browser default headers/footers */
                    header, footer { display: none !important; }
                }
                
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 3px solid #f59e0b; 
                    padding-bottom: 20px; 
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 20px;
                }
                
                .logo { width: 100px; height: auto; }
                .school-info h1 { margin: 0; color: #1e40af; font-size: 28px; text-transform: uppercase; }
                .school-info p { margin: 5px 0; color: #555; font-size: 14px; }
                .school-website { color: #f59e0b; font-weight: bold; text-decoration: none; }
                
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    opacity: 0.1;
                    z-index: -1;
                    width: 60%;
                    pointer-events: none;
                }
                
                .summary { 
                    margin: 20px 0; 
                    padding: 15px; 
                    background-color: #f0f9ff; 
                    border-left: 5px solid #1e40af; 
                    border-radius: 4px;
                }
                .summary h3 { margin-top: 0; color: #1e40af; }
                
                table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                th, td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; }
                th { background-color: #1e40af; color: white; font-weight: bold; text-transform: uppercase; font-size: 12px; }
                tr:nth-child(even) { background-color: #f8fafc; }
                tr:hover { background-color: #f1f5f9; }
                
                .grade-badge { padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 12px; }
                .grade-A { background-color: #dcfce7; color: #166534; }
                .grade-B { background-color: #dbeafe; color: #1e40af; }
                .grade-C { background-color: #fef3c7; color: #92400e; }
                .grade-D { background-color: #fed7aa; color: #c2410c; }
                .grade-F { background-color: #fee2e2; color: #dc2626; }
                
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
                
                @media print {
                    body { padding: 0; }
                    .no-print { display: none; }
                    th { background-color: #1e40af !important; color: white !important; -webkit-print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            ' . ($logo_data ? '<img src="' . $logo_data . '" class="watermark" alt="Watermark">' : '') . '
            <div class="report-container">
                <div class="header">
                    ' . ($logo_data ? '<img src="' . $logo_data . '" class="logo" alt="School Logo">' : '') . '
                    <div class="school-info">
                        <h1>Northland Schools Kano</h1>
                        <p>Excellence in Education</p>
                        <p>Website: <a href="http://nskn.com.ng/" class="school-website">http://nskn.com.ng/</a></p>
                        <p>Generated on: ' . date('F j, Y \a\t g:i A') . '</p>
                    </div>
                </div>';
        
        if (!empty($results)) {
            $html .= '
            <div class="summary">
                <h3>Result Summary</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div><strong>Class:</strong> ' . htmlspecialchars($results[0]['class_name']) . '</div>
                    <div><strong>Subject:</strong> ' . htmlspecialchars($results[0]['subject_name']) . '</div>
                    <div><strong>Session:</strong> ' . htmlspecialchars($results[0]['session_name']) . '</div>
                    <div><strong>Term:</strong> ' . htmlspecialchars($results[0]['term_name']) . '</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>CA Score</th>
                        <th>Exam Score</th>
                        <th>Total Score</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($results as $result) {
                $grade_class = 'grade-' . substr($result['grade'], 0, 1);
                if ($result['grade'] === 'A+') $grade_class = 'grade-A';
                
                $html .= '
                    <tr>
                        <td style="font-family: monospace;">' . htmlspecialchars($result['admission_number']) . '</td>
                        <td>' . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . '</td>
                        <td>' . htmlspecialchars($result['ca_score']) . '</td>
                        <td>' . htmlspecialchars($result['exam_score']) . '</td>
                        <td><strong>' . htmlspecialchars($result['total_score']) . '</strong></td>
                        <td><span class="grade-badge ' . $grade_class . '">' . htmlspecialchars($result['grade']) . '</span></td>
                    </tr>';
            }
            
            $html .= '
                </tbody>
            </table>';
        }
        
        $html .= '
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Northland Schools Kano. All Rights Reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}

try {
    $database = new Database();
    $data_handler = new ViewResultsData($database);

    $profile = $data_handler->getTeacherProfile();
    $sessions = $data_handler->getAcademicSessions();
    $teacher_classes_subjects = $data_handler->getTeacherClassesAndSubjects();

    // Get filter values
    $selected_session = $_GET['session_id'] ?? null;
    $selected_term = $_GET['term_id'] ?? null;
    $selected_class_subject = $_GET['class_subject'] ?? null;
    
    $results = [];
    $selected_class_name = '';
    $selected_subject_name = '';

    if ($selected_session && $selected_term && $selected_class_subject) {
        list($class_id, $subject_id) = explode('-', $selected_class_subject);
        
        // Get selected class and subject names for display
        foreach ($teacher_classes_subjects as $cs) {
            if ($cs['class_id'] == $class_id && $cs['subject_id'] == $subject_id) {
                $selected_class_name = $cs['class_name'];
                $selected_subject_name = $cs['subject_name'];
                break;
            }
        }
        
        $student_ids = $_GET['student_ids'] ?? [];
        if (!empty($student_ids)) {
            $student_ids = is_array($student_ids) ? $student_ids : [$student_ids];
        }
        
        $results = $data_handler->getResults($selected_session, $selected_term, $class_id, $subject_id, $student_ids);
    }

    // Handle exports
    if (isset($_GET['export'])) {
        $export_type = $_GET['export'];
        $filename = "results_" . date('Y-m-d_H-i-s');
        
        if ($export_type === 'excel') {
            $data_handler->exportToExcel($results, $filename);
        } elseif ($export_type === 'pdf') {
            $data_handler->exportToPDF($results, $filename);
        }
    }

} catch (Exception $e) {
    error_log("View results page error: " . $e->getMessage());
    if (strpos($e->getMessage(), 'Teacher profile not found') !== false) {
        session_destroy();
        header("Location: login-form.php?error=auth");
        exit();
    }
    $error_message = "An error occurred while loading the results page.";
}

// --- AJAX ENDPOINT FOR FETCHING TERMS ---
if (isset($_GET['action']) && $_GET['action'] === 'get_terms' && isset($_GET['session_id'])) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    try {
        $database = new Database();
        $data_handler = new ViewResultsData($database);
        $session_id = (int)$_GET['session_id'];
        $terms = $data_handler->getTermsBySession($session_id);
        echo json_encode(['success' => true, 'data' => $terms]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --- AJAX ENDPOINT FOR UPDATING RESULT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_result') {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get and validate inputs
        $student_id = (int)$_POST['student_id'];
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        $session_id = (int)$_POST['session_id'];
        $term_id = (int)$_POST['term_id'];
        $ca_score = (float)$_POST['ca_score'];
        $exam_score = (float)$_POST['exam_score'];
        
        // Calculate total and grade
        $total_score = $ca_score + $exam_score;
        
        // Helper function for grade (duplicated from ResultsData for now to keep this file self-contained or we could instantiate ResultsData)
        $calculateGrade = function($score) {
            if ($score >= 90) return 'A+';
            if ($score >= 80) return 'A';
            if ($score >= 70) return 'B';
            if ($score >= 60) return 'C';
            if ($score >= 50) return 'D';
            if ($score >= 40) return 'E';
            return 'F';
        };
        $grade = $calculateGrade($total_score);
        
        // Update database
        $query = "
            UPDATE student_results 
            SET ca_score = :ca_score, 
                exam_score = :exam_score, 
                total_score = :total_score, 
                grade = :grade,
                updated_at = NOW()
            WHERE student_id = :student_id 
            AND class_id = :class_id 
            AND subject_id = :subject_id 
            AND session_id = :session_id 
            AND term_id = :term_id
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':ca_score' => $ca_score,
            ':exam_score' => $exam_score,
            ':total_score' => $total_score,
            ':grade' => $grade,
            ':student_id' => $student_id,
            ':class_id' => $class_id,
            ':subject_id' => $subject_id,
            ':session_id' => $session_id,
            ':term_id' => $term_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Result updated successfully', 'new_grade' => $grade, 'new_total' => $total_score]);
        } else {
            // Check if it failed or just no changes
            echo json_encode(['success' => true, 'message' => 'No changes made or record not found']);
        }
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating result: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - Northland Schools Kano</title>
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
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
        
        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        
        .dashboard-card {
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar {
            transition: all var(--transition-speed) ease;
            width: var(--sidebar-width);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .main-content {
            transition: all var(--transition-speed) ease;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                z-index: 20;
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
                z-index: 15;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
        
        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .sidebar-link.active {
            background-color: #1e40af !important;
        }
        
        .mobile-header {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .desktop-header {
                display: none;
            }
        }
        
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 10000;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            opacity: 0;
            transform: translateY(-20px);
        }
        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        .notification.success { background-color: #10b981; color: white; }
        .notification.error { background-color: #ef4444; color: white; }
        
        .grade-A { background-color: #dcfce7; color: #166534; }
        .grade-B { background-color: #dbeafe; color: #1e40af; }
        .grade-C { background-color: #fef3c7; color: #92400e; }
        .grade-D { background-color: #fed7aa; color: #c2410c; }
        .grade-F { background-color: #fee2e2; color: #dc2626; }
    </style>
</head>
<body class="flex">
    <div id="notificationContainer"></div>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content bg-gray-100 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-md p-4 sticky top-0 z-30">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">View Student Results</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <i class="fas fa-bell text-gray-500 text-xl cursor-pointer hover:text-nskblue transition"></i>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">3</span>
                    </div>
                    
                    <div class="flex items-center space-x-2 cursor-pointer">
                        <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                            <?= $profile['initials'] ?>
                        </div>
                        <div class="hidden md:block">
                            <p class="text-sm font-semibold text-nsknavy"><?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></p>
                            <p class="text-xs text-gray-600"><?= htmlspecialchars($profile['specialization']) ?> Teacher</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 p-6 overflow-y-auto">
            
            <!-- Filters Card -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 border-l-4 border-nskblue">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-filter mr-2 text-nskblue"></i> Filter Results
                </h3>
                <form method="GET" action="view_results.php" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    
                    <!-- Session Filter -->
                    <div>
                        <label for="session_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Session</label>
                        <select name="session_id" id="session_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-nskblue focus:ring focus:ring-nskblue focus:ring-opacity-50 transition" required>
                            <option value="">Select Session</option>
                            <?php foreach ($sessions as $session): ?>
                                <option value="<?= $session['id'] ?>" <?= $session_id == $session['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($session['session_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Term Filter -->
                    <div>
                        <label for="term_id" class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                        <select name="term_id" id="term_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-nskblue focus:ring focus:ring-nskblue focus:ring-opacity-50 transition" required>
                            <option value="">Select Term</option>
                            <?php foreach ($terms as $term): ?>
                                <option value="<?= $term['id'] ?>" <?= $term_id == $term['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($term['term_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Class & Subject Filter -->
                    <div class="md:col-span-2">
                        <label for="class_subject_id" class="block text-sm font-medium text-gray-700 mb-1">Class & Subject</label>
                        <select name="class_subject_id" id="class_subject_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-nskblue focus:ring focus:ring-nskblue focus:ring-opacity-50 transition" required>
                            <option value="">Select Class / Subject</option>
                            <?php foreach ($teacher_classes as $tc): ?>
                                <?php 
                                    // Option for Class Only (Broadsheet)
                                    $class_val = $tc['id']; // Just Class ID
                                    $selected = ($class_id == $tc['id'] && !$subject_id) ? 'selected' : '';
                                ?>
                                <option value="<?= $class_val ?>" <?= $selected ?> class="font-bold text-nskblue">
                                    <?= htmlspecialchars($tc['class_name']) ?> (Broadsheet View)
                                </option>
                                
                                <?php 
                                    // Fetch subjects for this class to show individual options
                                    // Note: This might be inefficient inside a loop if not optimized. 
                                    // Better to fetch all at once. But for now, let's use what we have or modify ResultsData.
                                    // Actually, getTeacherClasses only returns classes now. 
                                    // We need a way to select Subject too if we want standard view.
                                    // I changed it to getTeacherClasses. I should probably revert or add a new method 
                                    // to get Class-Subject pairs for the dropdown if we still want standard view.
                                    
                                    // Let's use a new method or logic here.
                                    // For this implementation, I will assume the user can select a Class (for Broadsheet)
                                    // OR a Class-Subject (for Standard).
                                    // I need to fetch subjects for each class here.
                                    $subjects = $data_handler->getClassSubjects($tc['id']);
                                    foreach($subjects as $subj):
                                        $val = $tc['id'] . '-' . $subj['id'];
                                        $sel = ($class_id == $tc['id'] && $subject_id == $subj['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $val ?>" <?= $sel ?>>
                                        &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($tc['class_name']) ?> - <?= htmlspecialchars($subj['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" class="bg-nskblue hover:bg-blue-800 text-white font-bold py-2 px-6 rounded-lg shadow transition transform hover:scale-105 flex items-center">
                            <i class="fas fa-search mr-2"></i> View Results
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Table Card -->
            <?php if ($class_id && $session_id && $term_id): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">
                        <?php if ($is_broadsheet_view): ?>
                            Broadsheet Results
                        <?php else: ?>
                            Student Results
                        <?php endif; ?>
                    </h3>
                    <div class="space-x-2">
                        <button onclick="exportResults('excel')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded shadow text-sm transition">
                            <i class="fas fa-file-excel mr-1"></i> Excel
                        </button>
                        <button onclick="exportResults('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded shadow text-sm transition">
                            <i class="fas fa-file-pdf mr-1"></i> PDF
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <?php if ($is_broadsheet_view): ?>
                                    <?php foreach ($subjects_list as $subj): ?>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><?= htmlspecialchars($subj) ?></th>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">CA Score</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Exam Score</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($is_broadsheet_view): ?>
                                <?php if (empty($broadsheet_results)): ?>
                                    <tr><td colspan="100%" class="px-6 py-4 text-center text-gray-500">No results found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($broadsheet_results as $student): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($student['name']) ?></td>
                                            <?php foreach ($subjects_list as $subj): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-700">
                                                    <?= isset($student['subjects'][$subj]) ? htmlspecialchars($student['subjects'][$subj]) : '-' ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (empty($results)): ?>
                                    <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No results found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($results as $row): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['student_id']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-700"><?= htmlspecialchars($row['ca_score']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-700"><?= htmlspecialchars($row['exam_score']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-nskblue"><?= htmlspecialchars($row['total_score']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?= $row['grade'] === 'F' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                                    <?= htmlspecialchars($row['grade']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <button onclick='viewStudentResult(<?= json_encode($row) ?>)' class="text-nskblue hover:text-blue-900 mr-3" title="View Result">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick='openEditModal(<?= json_encode($row) ?>)' class="text-nskgold hover:text-yellow-600" title="Edit Result">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Average Score</p>
                        <p class="text-xl font-bold text-nskblue">
                            <?= number_format(array_sum(array_column($results, 'total_score')) / count($results), 1) ?>
                        </p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Highest Score</p>
                        <p class="text-xl font-bold text-nskgreen">
                            <?= max(array_column($results, 'total_score')) ?>
                        </p>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Lowest Score</p>
                        <p class="text-xl font-bold text-nskgold">
                            <?= min(array_column($results, 'total_score')) ?>
                        </p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Pass Rate</p>
                        <p class="text-xl font-bold text-purple-600">
                            <?php
                            $pass_count = count(array_filter($results, fn($r) => $r['total_score'] >= 40));
                            echo number_format(($pass_count / count($results)) * 100, 1) . '%';
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php elseif ($selected_session && $selected_term && $selected_class_subject): ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Results Found</h3>
                <p class="text-gray-500 mb-4">No results found for the selected filters.</p>
                <a href="results.php" class="bg-nskblue text-white px-6 py-2 rounded-lg hover:bg-nsknavy transition inline-flex items-center">
                    <i class="fas fa-upload mr-2"></i>Upload Results
                </a>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <i class="fas fa-chart-bar text-5xl text-nskblue mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">View Student Results</h3>
                <p class="text-gray-500">Select academic session, term, class, and subject to view results.</p>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </main>

    <!-- Edit Result Modal -->
    <div id="editResultModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-bold text-nsknavy" id="modalTitle">Edit Result</h3>
                <div class="mt-2 px-2 py-3">
                    <input type="hidden" id="editStudentId">
                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Student Name</label>
                        <p id="editStudentName" class="text-gray-600 font-medium"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">CA Score</label>
                            <input type="number" id="editCaScore" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:border-nskblue" step="0.1" min="0">
                        </div>
                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Exam Score</label>
                            <input type="number" id="editExamScore" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:border-nskblue" step="0.1" min="0">
                        </div>
                    </div>
                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Total Score</label>
                        <input type="text" id="editTotalScore" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight bg-gray-100 font-bold" readonly>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="saveResultBtn" onclick="saveResult()" class="px-4 py-2 bg-nskblue text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-nsknavy focus:outline-none transition">
                        Save Changes
                    </button>
                    <button onclick="closeEditModal()" class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Result Modal -->
    <div id="viewResultModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-bold text-nsknavy">Student Result Details</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">Student Name</span>
                        <p id="viewStudentName" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                    <div class="mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase">Admission Number</span>
                        <p id="viewAdmissionNo" class="text-gray-700 font-mono"></p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-4 text-center">
                    <div class="bg-blue-50 p-2 rounded">
                        <span class="block text-xs text-gray-500">CA Score</span>
                        <span id="viewCaScore" class="text-lg font-bold text-nskblue"></span>
                    </div>
                    <div class="bg-blue-50 p-2 rounded">
                        <span class="block text-xs text-gray-500">Exam Score</span>
                        <span id="viewExamScore" class="text-lg font-bold text-nskblue"></span>
                    </div>
                    <div class="bg-indigo-50 p-2 rounded">
                        <span class="block text-xs text-gray-500">Total</span>
                        <span id="viewTotalScore" class="text-lg font-bold text-nsknavy"></span>
                    </div>
                </div>
                <div class="text-center mb-6">
                    <span class="block text-xs text-gray-500 mb-1">Grade</span>
                    <span id="viewGrade" class="inline-block px-4 py-1 rounded-full text-sm font-bold"></span>
                </div>
                
                <div class="flex space-x-3">
                    <button id="viewPrintBtn" class="flex-1 px-4 py-2 bg-nskgold text-white text-base font-medium rounded-md shadow-sm hover:bg-amber-600 focus:outline-none transition">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <button onclick="closeViewModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-300 focus:outline-none transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i>${message}`;

            container.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        function toggleSelectAll(checkbox) {
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            studentCheckboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function selectAllStudents() {
            document.getElementById('selectAll').checked = true;
            toggleSelectAll(document.getElementById('selectAll'));
            showNotification('All students selected', 'success');
        }

        function clearSelection() {
            document.getElementById('selectAll').checked = false;
            toggleSelectAll(document.getElementById('selectAll'));
            showNotification('Selection cleared', 'success');
        }

        function getSelectedStudentIds() {
            const selected = [];
            document.querySelectorAll('.student-checkbox:checked').forEach(cb => {
                selected.push(cb.value);
            });
            return selected;
        }

        function exportResults(format) {
            const selectedIds = getSelectedStudentIds();
            const urlParams = new URLSearchParams(window.location.search);
            
            if (selectedIds.length > 0) {
                selectedIds.forEach(id => {
                    urlParams.append('student_ids[]', id);
                });
                showNotification(`Exporting ${selectedIds.length} selected student(s) as ${format.toUpperCase()}`, 'success');
            } else {
                showNotification('Exporting all results as ' + format.toUpperCase(), 'success');
            }
            
            urlParams.set('export', format);
            window.location.href = 'view_results.php?' + urlParams.toString();
        }

        function viewStudentResult(studentData) {
            document.getElementById('viewStudentName').textContent = studentData.first_name + ' ' + studentData.last_name;
            document.getElementById('viewAdmissionNo').textContent = studentData.admission_number;
            document.getElementById('viewCaScore').textContent = studentData.ca_score;
            document.getElementById('viewExamScore').textContent = studentData.exam_score;
            document.getElementById('viewTotalScore').textContent = studentData.total_score;
            
            const gradeSpan = document.getElementById('viewGrade');
            gradeSpan.textContent = studentData.grade;
            
            // Reset classes
            gradeSpan.className = 'inline-block px-4 py-1 rounded-full text-sm font-bold';
            
            // Add grade color class
            let gradeClass = 'grade-' + studentData.grade.charAt(0);
            if (studentData.grade === 'A+') gradeClass = 'grade-A';
            gradeSpan.classList.add(gradeClass);
            
            // Setup print button
            document.getElementById('viewPrintBtn').onclick = function() {
                printStudentResult(studentData.student_id);
            };
            
            document.getElementById('viewResultModal').classList.remove('hidden');
        }

        function closeViewModal() {
            document.getElementById('viewResultModal').classList.add('hidden');
        }

        function editStudentResult(studentData) {
            document.getElementById('editStudentId').value = studentData.student_id;
            document.getElementById('editStudentName').textContent = studentData.first_name + ' ' + studentData.last_name;
            document.getElementById('editCaScore').value = studentData.ca_score;
            document.getElementById('editExamScore').value = studentData.exam_score;
            document.getElementById('editTotalScore').value = studentData.total_score;
            
            document.getElementById('editResultModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editResultModal').classList.add('hidden');
        }

        // Auto-calculate total in modal
        ['editCaScore', 'editExamScore'].forEach(id => {
            document.getElementById(id).addEventListener('input', function() {
                const ca = parseFloat(document.getElementById('editCaScore').value) || 0;
                const exam = parseFloat(document.getElementById('editExamScore').value) || 0;
                document.getElementById('editTotalScore').value = (ca + exam).toFixed(1); // Keep decimal for precision if needed, or Math.round
            });
        });

        function saveResult() {
            const studentId = document.getElementById('editStudentId').value;
            const caScore = document.getElementById('editCaScore').value;
            const examScore = document.getElementById('editExamScore').value;
            
            // Get current filters
            const sessionId = document.querySelector('select[name="session_id"]').value;
            const termId = document.querySelector('select[name="term_id"]').value;
            const classSubject = document.querySelector('select[name="class_subject"]').value;
            const [classId, subjectId] = classSubject.split('-');
            
            const saveBtn = document.getElementById('saveResultBtn');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            
            const payload = {
                action: 'update_result',
                student_id: studentId,
                ca_score: caScore,
                exam_score: examScore,
                session_id: sessionId,
                term_id: termId,
                class_id: classId,
                subject_id: subjectId
            };
            
            fetch('view_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(payload).toString()
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('Result updated successfully', 'success');
                    closeEditModal();
                    // Reload page to reflect changes (simplest way)
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error while saving', 'error');
            })
            .finally(() => {
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            });
        }

        function printStudentResult(studentId) {
            // Open a new window with the PDF export for this single student
            const url = `view_results.php?export=pdf&student_ids[]=${studentId}&session_id=${document.querySelector('select[name="session_id"]').value}&term_id=${document.querySelector('select[name="term_id"]').value}&class_subject=${document.querySelector('select[name="class_subject"]').value}`;
            window.open(url, '_blank');
        }

        // AJAX for Term Fetching
        document.querySelector('select[name="session_id"]').addEventListener('change', function() {
            const sessionId = this.value;
            const termSelect = document.querySelector('select[name="term_id"]');
            
            if (!sessionId) {
                termSelect.innerHTML = '<option value="">Select Term</option>';
                return;
            }
            
            // Show loading state
            termSelect.innerHTML = '<option>Loading...</option>';
            termSelect.disabled = true;
            
            fetch(`view_results.php?action=get_terms&session_id=${sessionId}`)
                .then(response => response.json())
                .then(result => {
                    termSelect.innerHTML = '<option value="">Select Term</option>';
                    termSelect.disabled = false;
                    
                    if (result.success && result.data.length > 0) {
                        result.data.forEach(term => {
                            const option = document.createElement('option');
                            option.value = term.id;
                            option.textContent = term.term_name;
                            if (term.is_current == 1) {
                                option.selected = true;
                            }
                            termSelect.appendChild(option);
                        });
                        showNotification('Terms updated successfully', 'success');
                    } else {
                        showNotification('No terms found for this session', 'info');
                    }
                })
                .catch(error => {
                    console.error('Error fetching terms:', error);
                    termSelect.innerHTML = '<option value="">Error loading terms</option>';
                    termSelect.disabled = false;
                    showNotification('Error loading terms', 'error');
                });
        });

        // Auto-submit only when term or class/subject changes
        document.querySelector('select[name="term_id"]').addEventListener('change', function() {
            if (this.value && document.querySelector('select[name="session_id"]').value) {
                this.form.submit();
            }
        });

        document.querySelector('select[name="class_subject"]').addEventListener('change', function() {
            if (this.value && document.querySelector('select[name="session_id"]').value && document.querySelector('select[name="term_id"]').value) {
                this.form.submit();
            }
        });

        // Global search functionality
        document.getElementById('globalSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            console.log('View Results page loaded successfully');
        });
    </script>
</body>
</html>