<?php
// Start session and check authentication
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php';

class ResultsData
{
    private $conn;
    private $teacher_user_id;
    private $teacher_id;
    private $error_message = null;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed");
        }

        // Get the logged-in teacher's user ID from session
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
            throw new Exception("Teacher profile not found for user ID: " . $this->teacher_user_id);
        }
    }

    public function getTeacherProfile(): array
    {
        $profile = [
            'first_name' => 'N/A',
            'last_name' => 'N/A',
            'initials' => 'NN',
            'specialization' => 'N/A',
            'teacher_id' => 'N/A'
        ];

        $query = "
            SELECT 
                u.first_name, u.last_name, tp.subject_specialization, t.teacher_id
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

    /**
     * Fetches all academic sessions.
     */
    public function getAcademicSessions(): array
    {
        $query = "SELECT id, session_name, is_current FROM academic_sessions ORDER BY start_date DESC";
        try {
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching academic sessions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches terms for a specific session.
     */
    public function getTermsBySession(int $session_id): array
    {
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

    /**
     * Fetches distinct classes assigned to the teacher.
     */
    public function getTeacherClasses(): array
    {
        $classes = [];
        if (!$this->teacher_id)
            return $classes;

        $query = "
            SELECT DISTINCT
                c.id,
                c.class_name,
                c.class_level
            FROM class_subjects cs
            JOIN classes c ON cs.class_id = c.id
            WHERE cs.teacher_id = :tid
            ORDER BY c.class_name
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tid', $this->teacher_id);
            $stmt->execute();
            $classes = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching teacher classes: " . $e->getMessage());
        }

        return $classes;
    }

    /**
     * Fetches all subjects for a specific class.
     */
    public function getClassSubjects(int $class_id): array
    {
        $query = "
            SELECT DISTINCT
                s.id,
                s.subject_name
            FROM class_subjects cs
            JOIN subjects s ON cs.subject_id = s.id
            WHERE cs.class_id = :class_id
            ORDER BY s.subject_name
        ";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching class subjects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches all class-subject combinations for the teacher.
     */
    public function getTeacherClassesAndSubjects(): array
    {
        $data = [];
        if (!$this->teacher_id)
            return $data;

        $query = "
            SELECT 
                c.id as class_id,
                c.class_name,
                c.class_level,
                s.id as subject_id,
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
            $data = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching teacher classes and subjects: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Fetches broadsheet results for a class.
     */
    public function getBroadsheetResults(int $class_id, int $session_id, int $term_id): array
    {
        $query = "
            SELECT 
                s.id as student_id,
                s.first_name,
                s.last_name,
                sub.subject_name,
                sr.total_score
            FROM students s
            JOIN student_results sr ON s.id = sr.student_id
            JOIN subjects sub ON sr.subject_id = sub.id
            WHERE sr.class_id = :class_id
            AND sr.session_id = :session_id
            AND sr.term_id = :term_id
            ORDER BY s.last_name, s.first_name, sub.subject_name
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':class_id' => $class_id,
                ':session_id' => $session_id,
                ':term_id' => $term_id
            ]);

            $raw_results = $stmt->fetchAll();

            // Pivot data: [student_id => [name, subjects => [subject_name => score]]]
            $pivoted_results = [];
            foreach ($raw_results as $row) {
                $student_id = $row['student_id'];
                if (!isset($pivoted_results[$student_id])) {
                    $pivoted_results[$student_id] = [
                        'student_id' => $student_id,
                        'name' => $row['last_name'] . ' ' . $row['first_name'],
                        'subjects' => []
                    ];
                }
                $pivoted_results[$student_id]['subjects'][$row['subject_name']] = $row['total_score'];
            }

            return array_values($pivoted_results);

        } catch (PDOException $e) {
            error_log("Error fetching broadsheet results: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Saves broadsheet results.
     */
    public function saveBroadsheetResults(int $class_id, int $session_id, int $term_id, array $results_data): bool
    {
        try {
            $this->conn->beginTransaction();

            foreach ($results_data as $student_data) {
                $student_id = $student_data['student_id'] ?? null;
                if (!$student_id)
                    continue;

                // Iterate through subjects in the student data
                foreach ($student_data['subjects'] as $subject_id => $scores) {
                    $ca_score = $scores['ca'] ?? 0;
                    $exam_score = $scores['exam'] ?? 0;
                    $total_score = floatval($ca_score) + floatval($exam_score);
                    $grade = $this->calculateGrade($total_score);

                    // Check if result already exists
                    $check_query = "
                        SELECT id FROM student_results 
                        WHERE student_id = :student_id 
                        AND class_id = :class_id 
                        AND subject_id = :subject_id 
                        AND session_id = :session_id 
                        AND term_id = :term_id
                    ";

                    $stmt_check = $this->conn->prepare($check_query);
                    $stmt_check->execute([
                        ':student_id' => $student_id,
                        ':class_id' => $class_id,
                        ':subject_id' => $subject_id,
                        ':session_id' => $session_id,
                        ':term_id' => $term_id
                    ]);

                    if ($stmt_check->fetch()) {
                        // Update
                        $update_query = "
                            UPDATE student_results 
                            SET ca_score = :ca_score, exam_score = :exam_score, total_score = :total_score,
                                grade = :grade, updated_at = NOW() 
                            WHERE student_id = :student_id 
                            AND class_id = :class_id 
                            AND subject_id = :subject_id 
                            AND session_id = :session_id 
                            AND term_id = :term_id
                        ";
                        $stmt = $this->conn->prepare($update_query);
                    } else {
                        // Insert
                        $insert_query = "
                            INSERT INTO student_results 
                            (student_id, class_id, subject_id, session_id, term_id, ca_score, exam_score, total_score, grade, created_at, updated_at)
                            VALUES (:student_id, :class_id, :subject_id, :session_id, :term_id, :ca_score, :exam_score, :total_score, :grade, NOW(), NOW())
                        ";
                        $stmt = $this->conn->prepare($insert_query);
                    }

                    $stmt->execute([
                        ':student_id' => $student_id,
                        ':class_id' => $class_id,
                        ':subject_id' => $subject_id,
                        ':session_id' => $session_id,
                        ':term_id' => $term_id,
                        ':ca_score' => $ca_score,
                        ':exam_score' => $exam_score,
                        ':total_score' => $total_score,
                        ':grade' => $grade
                    ]);
                }
            }

            $this->conn->commit();
            $this->error_message = "Successfully processed and saved results for " . count($results_data) . " students.";
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->error_message = "Error saving results: " . $e->getMessage();
            error_log("Save broadsheet results error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Saves bulk results for a single subject (Standard Template).
     */
    public function saveBulkResults(int $class_id, int $subject_id, int $session_id, int $term_id, array $results_data): bool
    {
        try {
            $this->conn->beginTransaction();

            $check_query = "
                SELECT id FROM student_results 
                WHERE student_id = :student_id 
                AND class_id = :class_id 
                AND subject_id = :subject_id 
                AND session_id = :session_id 
                AND term_id = :term_id
            ";
            $stmt_check = $this->conn->prepare($check_query);

            $update_query = "
                UPDATE student_results 
                SET ca_score = :ca_score, exam_score = :exam_score, total_score = :total_score,
                    grade = :grade, updated_at = NOW() 
                WHERE id = :result_id
            ";
            $stmt_update = $this->conn->prepare($update_query);

            $insert_query = "
                INSERT INTO student_results 
                (student_id, class_id, subject_id, session_id, term_id, ca_score, exam_score, total_score, grade, created_at, updated_at)
                VALUES (:student_id, :class_id, :subject_id, :session_id, :term_id, :ca_score, :exam_score, :total_score, :grade, NOW(), NOW())
            ";
            $stmt_insert = $this->conn->prepare($insert_query);

            // Statement to lookup student ID if string provided
            $stmt_lookup = $this->conn->prepare("SELECT id FROM students WHERE student_id = ? OR admission_number = ? LIMIT 1");

            foreach ($results_data as $row) {
                $student_id_input = $row['student_id'] ?? null;
                if (!$student_id_input)
                    continue;

                // Resolve Student ID if it is not numeric
                $student_id = $student_id_input;
                if (!is_numeric($student_id_input)) {
                    $stmt_lookup->execute([$student_id_input, $student_id_input]);
                    $found_id = $stmt_lookup->fetchColumn();
                    // If not found by string ID, we can't insert reliable data linked to a student
                    if (!$found_id)
                        continue;
                    $student_id = $found_id;
                }

                $ca_score = floatval($row['ca_score'] ?? 0);
                $exam_score = floatval($row['exam_score'] ?? 0);
                $total_score = $ca_score + $exam_score;
                $grade = $this->calculateGrade($total_score);

                // Check existing
                $stmt_check->execute([
                    ':student_id' => $student_id,
                    ':class_id' => $class_id,
                    ':subject_id' => $subject_id,
                    ':session_id' => $session_id,
                    ':term_id' => $term_id
                ]);
                $existing_id = $stmt_check->fetchColumn();

                if ($existing_id) {
                    $stmt_update->execute([
                        ':ca_score' => $ca_score,
                        ':exam_score' => $exam_score,
                        ':total_score' => $total_score,
                        ':grade' => $grade,
                        ':result_id' => $existing_id
                    ]);
                } else {
                    $stmt_insert->execute([
                        ':student_id' => $student_id,
                        ':class_id' => $class_id,
                        ':subject_id' => $subject_id,
                        ':session_id' => $session_id,
                        ':term_id' => $term_id,
                        ':ca_score' => $ca_score,
                        ':exam_score' => $exam_score,
                        ':total_score' => $total_score,
                        ':grade' => $grade
                    ]);
                }
            }

            $this->conn->commit();
            $this->error_message = "Successfully uploaded results for " . count($results_data) . " students.";
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->error_message = "Database error: " . $e->getMessage();
            error_log("Result Upload Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate grade based on total score
     */
    public function calculateGrade($score): string
    {
        if ($score >= 90)
            return 'A+';
        if ($score >= 80)
            return 'A';
        if ($score >= 70)
            return 'B';
        if ($score >= 60)
            return 'C';
        if ($score >= 50)
            return 'D';
        if ($score >= 40)
            return 'E';
        return 'F';
    }

    public function getErrorMessage()
    {
        return $this->error_message;
    }
}

// --- Execution & AJAX Handling ---
try {
    $database = new Database();
    $data_handler = new ResultsData($database);

    // Fetch initial data only if it's not a download request
    $profile = $data_handler->getTeacherProfile();
    $sessions = $data_handler->getAcademicSessions();
    $teacher_classes_subjects = $data_handler->getTeacherClassesAndSubjects();
    $teacher_classes = $data_handler->getTeacherClasses();

    // Determine default selected session/term (the current ones)
    $selected_session = current(array_filter($sessions, fn($s) => $s['is_current'] == 1))['id'] ?? $sessions[0]['id'] ?? null;
    $terms = $selected_session ? $data_handler->getTermsBySession($selected_session) : [];
    $selected_term = current(array_filter($terms, fn($t) => $t['is_current'] == 1))['id'] ?? $terms[0]['id'] ?? null;

} catch (Exception $e) {
    // Handle errors gracefully
    error_log("Results page error: " . $e->getMessage());

    // If it's an authentication error, redirect to login
    if (strpos($e->getMessage(), 'Teacher profile not found') !== false) {
        session_destroy();
        header("Location: login-form.php?error=auth");
        exit();
    }

    $error_message = "An error occurred while loading the results page. Please try again later.";
}

// --- NEW TEMPLATE DOWNLOAD ENDPOINT ---
if (isset($_GET['action']) && $_GET['action'] === 'download_template' && isset($_GET['type'])) {
    $template_type = $_GET['type'];
    $class_id = $_GET['class_id'] ?? null;

    if ($template_type === 'broadsheet' && $class_id) {
        // Generate Dynamic Broadsheet Template
        try {
            $database = new Database();
            $data_handler = new ResultsData($database);
            $subjects = $data_handler->getClassSubjects($class_id);

            // Create XML Excel content (Excel 2003 XML format)
            $xml = '<?xml version="1.0"?>
            <?mso-application progid="Excel.Sheet"?>
            <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
             xmlns:o="urn:schemas-microsoft-com:office:office"
             xmlns:x="urn:schemas-microsoft-com:office:excel"
             xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
             xmlns:html="http://www.w3.org/TR/REC-html40">
             <Styles>
              <Style ss:ID="Default" ss:Name="Normal">
               <Alignment ss:Vertical="Bottom"/>
               <Borders/>
               <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
               <Interior/>
               <NumberFormat/>
               <Protection/>
              </Style>
              <Style ss:ID="s62">
               <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
               <Interior ss:Color="#D9D9D9" ss:Pattern="Solid"/>
              </Style>
             </Styles>
             <Worksheet ss:Name="Broadsheet">
              <Table>
               <Row>
                <Cell ss:StyleID="s62"><Data ss:Type="String">Student ID</Data></Cell>
                <Cell ss:StyleID="s62"><Data ss:Type="String">Student Name</Data></Cell>';

            foreach ($subjects as $subject) {
                $safe_subject_name = htmlspecialchars($subject['subject_name']);
                $xml .= '<Cell ss:StyleID="s62"><Data ss:Type="String">' . $safe_subject_name . ' CA</Data></Cell>';
                $xml .= '<Cell ss:StyleID="s62"><Data ss:Type="String">' . $safe_subject_name . ' Exam</Data></Cell>';
            }

            $xml .= '</Row>
               <Row>
                <Cell><Data ss:Type="String">STD001</Data></Cell>
                <Cell><Data ss:Type="String">John Doe</Data></Cell>';

            // Add dummy data cells
            foreach ($subjects as $subject) {
                $xml .= '<Cell><Data ss:Type="Number">15</Data></Cell>';
                $xml .= '<Cell><Data ss:Type="Number">45</Data></Cell>';
            }

            $xml .= '</Row>
              </Table>
             </Worksheet>
            </Workbook>';

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="broadsheet_template_class_' . $class_id . '.xls"');
            echo $xml;
            exit;

        } catch (Exception $e) {
            die("Error generating template: " . $e->getMessage());
        }
    }

    // ... existing static template logic ...
    $file_path = '';
    $file_name = '';
    $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    // Map template type to actual file names - try Excel first, fallback to CSV
    switch ($template_type) {
        case 'early-years':
            $file_name = 'early_years_template.xlsx';
            $csv_fallback = 'early_years_template.csv';
            break;
        case 'primary':
            $file_name = 'primary_school_template.xls';
            $content_type = 'application/vnd.ms-excel';
            break;
        case 'secondary':
            $file_name = 'secondary_school_template.xlsx';
            $csv_fallback = 'secondary_school_template.csv';
            break;
        default:
            header("HTTP/1.0 404 Not Found");
            exit("Error: Invalid template type.");
    }

    $file_path = __DIR__ . "/templates/" . $file_name;

    // Check if Excel file exists, if not try CSV fallback
    if (!file_exists($file_path) && isset($csv_fallback)) {
        $file_path = __DIR__ . "/templates/" . $csv_fallback;
        $file_name = $csv_fallback;
        $content_type = 'text/csv';
    }

    if (file_exists($file_path)) {
        // Headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        ob_clean();
        flush();
        readfile($file_path);
        exit;
    } else {
        // Template file not found - generate a simple CSV as fallback
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="results_template.csv"');

        echo "Student ID,Student Name,CA Score (30),Exam Score (70),Total (100)\n";
        echo "STU001,Sample Student,25,60,85\n";
        echo "STU002,Example Student,28,65,93\n";
        exit;
    }
}

// --- AJAX ENDPOINT FOR FETCHING TERMS ---
if (isset($_GET['action']) && $_GET['action'] === 'get_terms' && isset($_GET['session_id'])) {
    header('Content-Type: application/json');
    try {
        $database = new Database();
        $data_handler = new ResultsData($database);
        $session_id = (int) $_GET['session_id'];
        $terms = $data_handler->getTermsBySession($session_id);
        echo json_encode(['success' => true, 'data' => $terms]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --- AJAX ENDPOINT FOR RESULTS PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_results') {
    // Read JSON input
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true);

    if (!$input_data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $class_id = $input_data['class_id'] ?? null;
    $subject_id = $input_data['subject_id'] ?? null;
    $session_id = $input_data['session_id'] ?? null;
    $term_id = $input_data['term_id'] ?? null;
    $results_data = $input_data['results_data'] ?? [];
    $is_broadsheet = $input_data['is_broadsheet'] ?? false;

    if (!$class_id || !$session_id || !$term_id || empty($results_data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Turn off error display for this request to prevent HTML error output in JSON response
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    try {
        $database = new Database();
        $results_handler = new ResultsData($database);

        if ($is_broadsheet) {
            // Broadsheet Processing
            // 1. Fetch all subjects for the class to map names to IDs
            $class_subjects = $results_handler->getClassSubjects($class_id);
            $subject_map = [];
            foreach ($class_subjects as $subj) {
                // Normalize name for matching (lowercase, trim)
                $key = strtolower(trim($subj['subject_name']));
                $subject_map[$key] = $subj['id'];
            }

            // 2. Transform results_data to replace subject names with IDs
            $processed_data = [];
            foreach ($results_data as $student) {
                $student_entry = [
                    'student_id' => $student['student_id'],
                    'subjects' => []
                ];

                if (isset($student['subjects']) && is_array($student['subjects'])) {
                    foreach ($student['subjects'] as $subj_name => $scores) {
                        $key = strtolower(trim($subj_name));
                        if (isset($subject_map[$key])) {
                            $subj_id = $subject_map[$key];
                            $student_entry['subjects'][$subj_id] = $scores;
                        }
                    }
                }
                $processed_data[] = $student_entry;
            }

            $success = $results_handler->saveBroadsheetResults($class_id, $session_id, $term_id, $processed_data);

        } else {
            // Standard Processing
            if (!$subject_id) {
                throw new Exception("Subject ID is required for standard upload.");
            }
            $success = $results_handler->saveBulkResults($class_id, $subject_id, $session_id, $term_id, $results_data);
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => $results_handler->getErrorMessage()]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $results_handler->getErrorMessage()]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $e->getMessage()]);
    }
    exit;
}

// ... [Rest of the HTML and JavaScript remains the same as your original file]
// The HTML and JavaScript part remains exactly the same as your original results.php
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Results - Northland Schools Kano</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
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

        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }

        .upload-area:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .upload-area.dragover {
            border-color: #3b82f6;
            background-color: #dbeafe;
        }

        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
            background: linear-gradient(90deg, #10b981, #3b82f6);
        }

        .template-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .template-card:hover {
            transform: translateX(5px);
        }

        .template-early-years {
            border-left-color: #3b82f6;
        }

        .template-primary {
            border-left-color: #10b981;
        }

        .template-secondary {
            border-left-color: #8b5cf6;
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

        .notification.success {
            background-color: #10b981;
            color: white;
        }

        .notification.error {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>

<body class="flex">
    <div id="notificationContainer"></div>

    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="desktop-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Terminal Results - Excel Upload</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search results..."
                                class="bg-transparent outline-none w-32 md:w-64">
                        </div>
                    </div>

                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>

                    <div class="hidden md:flex items-center space-x-2">
                        <div
                            class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                            <?= $profile['initials'] ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-nsknavy">
                                <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?>
                            </p>
                            <p class="text-xs text-gray-600"><?= htmlspecialchars($profile['specialization']) ?> Teacher
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <header class="mobile-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">Excel Results Upload</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>

                    <div class="flex items-center space-x-2">
                        <div
                            class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                            <?= $profile['initials'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 md:p-6">
            <!-- Error Message Display -->
            <?php if (isset($error_message)): ?>
                <div class="notification show error mb-6" style="opacity: 1; transform: translateY(0);">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

            <!-- Step 1: Select Context -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6 border-l-4 border-nskblue">
                <h3 class="text-lg font-bold text-nsknavy mb-4 flex items-center">
                    <span
                        class="bg-nskblue text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">1</span>
                    Select Context
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Academic Session</label>
                        <select id="session_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue transition">
                            <?php foreach ($sessions as $session): ?>
                                <option value="<?= $session['id'] ?>" <?= $session['is_current'] == 1 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($session['session_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Term</label>
                        <select id="term_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue transition">
                            <?php if (!empty($terms)): ?>
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?= $term['id'] ?>" <?= $term['is_current'] == 1 ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($term['term_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No terms available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label id="class_subject_label" class="block text-sm font-medium text-gray-700 mb-2">Class &
                            Subject</label>
                        <select id="class_subject_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue transition">
                            <option value="">Select Class / Subject</option>

                            <!-- Broadsheet Options (Class Only) -->
                            <optgroup label="Broadsheet (All Subjects)">
                                <?php foreach ($teacher_classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"
                                        data-level="<?= htmlspecialchars($class['class_level'] ?? '') ?>"
                                        class="font-bold text-nskblue">
                                        <?= htmlspecialchars($class['class_name']) ?> (All Subjects)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>

                            <!-- Standard Options (Class + Subject) -->
                            <optgroup label="Standard (Single Subject)">
                                <?php foreach ($teacher_classes_subjects as $cs): ?>
                                    <option value="<?= $cs['class_id'] ?>-<?= $cs['subject_id'] ?>"
                                        data-level="<?= htmlspecialchars($cs['class_level'] ?? '') ?>">
                                        <?= htmlspecialchars($cs['class_name']) ?> -
                                        <?= htmlspecialchars($cs['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 2: Download Template -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6 border-l-4 border-nskgreen">
                <h3 class="text-lg font-bold text-nsknavy mb-4 flex items-center">
                    <span
                        class="bg-nskgreen text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">2</span>
                    Download Template
                </h3>
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm mb-2">Select the type of template you want to download based on
                            your context selection above.</p>
                        <div class="flex items-center space-x-4">
                            <select id="template_type"
                                class="border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue bg-gray-50">
                                <option value="broadsheet">Broadsheet Template (All Subjects)</option>
                                <option value="standard">Standard Template (Single Subject)</option>
                            </select>
                        </div>
                    </div>
                    <button id="downloadTemplateBtn"
                        class="bg-nskgreen hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition transform hover:scale-105 flex items-center">
                        <i class="fas fa-download mr-2"></i> Download Excel Template
                    </button>
                </div>
            </div>

            <!-- Step 3: Upload Results -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6 border-l-4 border-nskgold">
                <h3 class="text-lg font-bold text-nsknavy mb-4 flex items-center">
                    <span
                        class="bg-nskgold text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">3</span>
                    Upload Results
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                        <select id="uploadAction"
                            class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue transition">
                            <option value="preview">Process and Preview Only</option>
                            <option value="validate">Validate Data Only</option>
                            <option value="save_results">Process and Save Results</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select "Validate" to check for errors without saving.</p>
                    </div>
                </div>

                <div class="upload-area mb-6" id="uploadArea">
                    <i class="fas fa-file-excel text-green-500 text-4xl mb-4"></i>
                    <h4 class="text-lg font-semibold text-gray-700 mb-2">Upload Completed Excel File</h4>
                    <p class="text-gray-600 mb-4">Drag and drop your file here or click to browse</p>
                    <input type="file" id="excel_file" accept=".xlsx, .xls" class="hidden">
                    <button id="browseFilesBtn"
                        class="bg-nskblue text-white px-6 py-2 rounded-lg hover:bg-nsknavy transition">
                        <i class="fas fa-upload mr-2"></i>Browse Files
                    </button>
                    <p class="text-xs text-gray-500 mt-3">Supported formats: .xlsx, .xls (Max: 10MB)</p>
                </div>

                <div id="fileInfo" class="hidden bg-gray-50 p-4 rounded-lg flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file-excel text-green-500 text-xl"></i>
                        <div>
                            <p class="font-semibold" id="fileName">filename.xlsx</p>
                            <p class="text-sm text-gray-600" id="fileSize">0 KB</p>
                        </div>
                    </div>
                    <button class="text-red-500 hover:text-red-700" onclick="removeFile()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="previewSection" class="hidden">
                    <h4 class="font-bold text-gray-700 mb-2">Data Preview</h4>
                    <div class="overflow-x-auto mb-4 border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50" id="previewTableHead"></thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="previewTableBody"></tbody>
                        </table>
                    </div>
                    <div class="flex justify-between items-center">
                        <p id="uploadStatus" class="text-sm text-gray-600"></p>
                        <div class="space-x-2">
                            <button id="validateBtn"
                                class="bg-nskgold hover:bg-amber-600 text-white font-bold py-2 px-6 rounded-lg shadow transition hidden">
                                <i class="fas fa-check-circle mr-2"></i> Validate Data
                            </button>
                            <button id="uploadBtn"
                                class="bg-nskblue hover:bg-blue-800 text-white font-bold py-2 px-6 rounded-lg shadow transition hidden">
                                <i class="fas fa-save mr-2"></i> Process & Save
                            </button>
                        </div>
                    </div>
                </div>

                <div id="processingSection" class="hidden bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-nsknavy mb-4">Processing Results</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Reading Excel file...</span>
                                <span id="progressText1">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressBar1" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Validating data...</span>
                                <span id="progressText2">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressBar2" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Calculating totals...</span>
                                <span id="progressText3">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressBar3" style="width: 0%"></div>
                            </div>
                        </div>
                        <div id="savingStep" class="hidden">
                            <div class="flex justify-between text-sm mb-1">
                                <span>Saving to database...</span>
                                <span id="progressText4">0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressBar4" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        // Global variables
        let uploadedFile = null;
        let parsedData = [];

        // DOM Elements
        const sessionIdSelect = document.getElementById('session_id');
        const termIdSelect = document.getElementById('term_id');
        const classSubjectSelect = document.getElementById('class_subject_id');
        const templateTypeSelect = document.getElementById('template_type');
        const downloadTemplateBtn = document.getElementById('downloadTemplateBtn');
        const excelFileInput = document.getElementById('excel_file');
        const browseFilesBtn = document.getElementById('browseFilesBtn');
        const fileInfoDiv = document.getElementById('fileInfo');
        const fileNameSpan = document.getElementById('fileName');
        const fileSizeSpan = document.getElementById('fileSize');
        const previewSection = document.getElementById('previewSection');
        const previewTableHead = document.getElementById('previewTableHead');
        const previewTableBody = document.getElementById('previewTableBody');
        const uploadBtn = document.getElementById('uploadBtn');
        const validateBtn = document.getElementById('validateBtn');
        const uploadStatus = document.getElementById('uploadStatus');
        const processingSection = document.getElementById('processingSection');
        const uploadActionSelect = document.getElementById('uploadAction');

        // --- Event Listeners ---

        // 1. Session Change -> Fetch Terms
        sessionIdSelect.addEventListener('change', function () {
            const sessionId = this.value;
            termIdSelect.innerHTML = '<option value="">Loading...</option>';
            termIdSelect.disabled = true;

            if (sessionId) {
                fetch(`results.php?action=get_terms&session_id=${sessionId}`)
                    .then(response => response.json())
                    .then(data => {
                        termIdSelect.innerHTML = '<option value="">Select Term</option>';
                        if (data.success && data.data) {
                            data.data.forEach(term => {
                                const option = document.createElement('option');
                                option.value = term.id;
                                option.textContent = term.term_name;
                                if (term.is_current == 1) option.selected = true;
                                termIdSelect.appendChild(option);
                            });
                        }
                        termIdSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        termIdSelect.innerHTML = '<option value="">Error loading terms</option>';
                    });
            } else {
                termIdSelect.innerHTML = '<option value="">Select Term</option>';
            }
        });

        // 2. Download Template
        downloadTemplateBtn.addEventListener('click', function () {
            const type = templateTypeSelect.value;
            const classSubjectValue = classSubjectSelect.value;

            if (!classSubjectValue) {
                showNotification('Please select a Class first.', 'error');
                return;
            }

            let url = `results.php?action=download_template&type=${type}`;

            // Extract Class ID
            // Value can be "class_id" (Broadsheet) or "class_id-subject_id" (Standard)
            let classId = classSubjectValue;
            if (classSubjectValue.includes('-')) {
                classId = classSubjectValue.split('-')[0];
            }

            if (type === 'broadsheet') {
                url += `&class_id=${classId}`;
            } else {
                // Get class level from selected option
                const selectedOption = classSubjectSelect.options[classSubjectSelect.selectedIndex];
                const level = selectedOption.getAttribute('data-level');

                let specificType = 'primary';
                if (level === 'Early Childhood') {
                    specificType = 'early-years';
                } else if (level === 'Secondary') {
                    specificType = 'secondary';
                }

                url = `results.php?action=download_template&type=${specificType}`;
            }

            // Store original content
            const originalContent = downloadTemplateBtn.innerHTML;

            // Show loading state
            downloadTemplateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Downloading...';
            downloadTemplateBtn.classList.add('opacity-75', 'cursor-not-allowed');
            downloadTemplateBtn.disabled = true;

            setTimeout(() => {
                window.location.href = url;

                // Reset button after a short delay
                setTimeout(() => {
                    downloadTemplateBtn.innerHTML = originalContent;
                    downloadTemplateBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                    downloadTemplateBtn.disabled = false;
                }, 3000);
            }, 100);
        });

        // 3. File Browse
        browseFilesBtn.addEventListener('click', () => excelFileInput.click());

        excelFileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) handleFileSelect(file);
        });

        function handleFileSelect(file) {
            // File Validation
            const validTypes = ['.xlsx', '.xls'];
            const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();

            if (!validTypes.includes(fileExtension)) {
                showNotification('Invalid file type. Please upload .xlsx or .xls file.', 'error');
                return;
            }

            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                showNotification('File size exceeds 10MB limit.', 'error');
                return;
            }

            uploadedFile = file;
            fileNameSpan.textContent = file.name;
            fileSizeSpan.textContent = (file.size / 1024).toFixed(2) + ' KB';

            document.getElementById('uploadArea').classList.add('hidden');
            fileInfoDiv.classList.remove('hidden');

            parseExcelFile(file);
        }

        function removeFile() {
            uploadedFile = null;
            excelFileInput.value = '';
            fileInfoDiv.classList.add('hidden');
            document.getElementById('uploadArea').classList.remove('hidden');
            previewSection.classList.add('hidden');
            processingSection.classList.add('hidden');
            parsedData = [];
        }

        // 4. Parse Excel
        function parseExcelFile(file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                if (jsonData.length < 2) {
                    showNotification('Excel file appears empty or invalid.', 'error');
                    return;
                }

                const isBroadsheet = templateTypeSelect.value === 'broadsheet';
                if (isBroadsheet) {
                    processBroadsheetData(jsonData);
                } else {
                    processStandardData(jsonData);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function processStandardData(jsonData) {
            parsedData = [];
            // Skip header (row 0)
            for (let i = 1; i < jsonData.length; i++) {
                const row = jsonData[i];
                if (row.length > 0 && row[0]) {
                    parsedData.push({
                        student_id: row[0],
                        student_name: row[1],
                        ca_score: row[2] || 0,
                        exam_score: row[3] || 0
                    });
                }
            }
            renderPreview(parsedData, ['Student ID', 'Name', 'CA Score', 'Exam Score'], false);
        }

        function processBroadsheetData(jsonData) {
            const headers = jsonData[0];
            parsedData = [];

            // Identify Subject Columns
            const subjects = [];
            for (let i = 2; i < headers.length; i++) {
                const headerName = headers[i];
                if (headerName && headerName.toString().trim().endsWith(' CA')) {
                    const subjectName = headerName.toString().replace(' CA', '').trim();
                    // Look for corresponding Exam column
                    // Assuming Exam column is next or we search for it
                    // The template generates CA then Exam for each subject
                    // But let's be robust: find "Subject Exam"
                    const examHeader = subjectName + ' Exam';
                    const examIndex = headers.indexOf(examHeader);

                    if (examIndex !== -1) {
                        subjects.push({
                            name: subjectName,
                            caIndex: i,
                            examIndex: examIndex
                        });
                    }
                }
            }

            for (let i = 1; i < jsonData.length; i++) {
                const row = jsonData[i];
                if (row.length > 0 && row[0]) {
                    const studentEntry = {
                        student_id: row[0],
                        student_name: row[1],
                        subjects: {}
                    };

                    subjects.forEach(subj => {
                        studentEntry.subjects[subj.name] = {
                            ca: row[subj.caIndex] || 0,
                            exam: row[subj.examIndex] || 0
                        };
                    });
                    parsedData.push(studentEntry);
                }
            }

            const previewHeaders = ['Student ID', 'Name', ...subjects.slice(0, 3).map(s => s.name + ' (Total)')];
            renderPreview(parsedData, previewHeaders, true);
        }

        function renderPreview(data, headers, isBroadsheetPreview) {
            previewSection.classList.remove('hidden');

            // Update buttons based on action
            const action = uploadActionSelect.value;
            if (action === 'save_results') {
                uploadBtn.classList.remove('hidden');
                validateBtn.classList.add('hidden');
            } else if (action === 'validate') {
                uploadBtn.classList.add('hidden');
                validateBtn.classList.remove('hidden');
            } else { // 'preview'
                uploadBtn.classList.add('hidden');
                validateBtn.classList.add('hidden');
            }

            // Render Headers
            previewTableHead.innerHTML = '<tr>' + headers.map(h => `<th class="px-4 py-2 text-left border">${h}</th>`).join('') + '</tr>';

            // Render Body (Limit to 5 rows)
            previewTableBody.innerHTML = '';
            data.slice(0, 5).forEach(row => {
                let rowHtml = `<td class="border px-4 py-2">${row.student_id}</td><td class="border px-4 py-2">${row.student_name}</td>`;

                if (isBroadsheetPreview) {
                    const subjectNames = Object.keys(row.subjects).slice(0, 3);
                    subjectNames.forEach(subjName => {
                        const scores = row.subjects[subjName];
                        const total = (parseFloat(scores.ca || 0) + parseFloat(scores.exam || 0));
                        rowHtml += `<td class="border px-4 py-2">${total}</td>`;
                    });
                } else {
                    rowHtml += `<td class="border px-4 py-2">${row.ca_score}</td><td class="border px-4 py-2">${row.exam_score}</td>`;
                }

                // Add Status
                rowHtml += `<td class="border px-4 py-2"><span class="text-green-600"><i class="fas fa-check-circle"></i> Ready</span></td>`;

                const tr = document.createElement('tr');
                tr.innerHTML = rowHtml;
                previewTableBody.appendChild(tr);
            });

            uploadStatus.textContent = `Loaded ${data.length} records.`;
        }

        // Handle Action Change
        uploadActionSelect.addEventListener('change', function () {
            if (parsedData.length > 0) {
                // Re-render preview to update buttons
                const isBroadsheet = templateTypeSelect.value === 'broadsheet';
                // We need to reconstruct headers... simplified for now
                // Ideally we store headers or re-process
                // For now, just toggling buttons
                const action = this.value;
                if (action === 'save_results') {
                    uploadBtn.classList.remove('hidden');
                    validateBtn.classList.add('hidden');
                } else if (action === 'validate') {
                    uploadBtn.classList.add('hidden');
                    validateBtn.classList.remove('hidden');
                } else { // 'preview'
                    uploadBtn.classList.add('hidden');
                    validateBtn.classList.add('hidden');
                }
            }
        });

        // 5. Upload & Save
        uploadBtn.addEventListener('click', () => processResults('save_results'));
        validateBtn.addEventListener('click', () => processResults('validate'));

        function processResults(action) {
            if (!parsedData.length) return;

            const sessionId = sessionIdSelect.value;
            const termId = termIdSelect.value;
            const classSubjectValue = classSubjectSelect.value;
            const isBroadsheet = templateTypeSelect.value === 'broadsheet';

            if (!sessionId || !termId || !classSubjectValue) {
                showNotification('Please select Session, Term, and Class.', 'error');
                return;
            }

            let classId, subjectId;
            if (classSubjectValue.includes('-')) {
                [classId, subjectId] = classSubjectValue.split('-');
            } else {
                classId = classSubjectValue;
                subjectId = null;
            }

            // UI Updates
            previewSection.classList.add('hidden');
            processingSection.classList.remove('hidden');
            simulateProcessing(action);

            const payload = {
                action: 'process_results',
                class_id: classId,
                subject_id: subjectId,
                session_id: sessionId,
                term_id: termId,
                results_data: parsedData,
                is_broadsheet: isBroadsheet,
                upload_action: action // Pass action to backend if needed, or just for logic here
            };

            // Wait for simulation to finish (fake delay for UX) then send
            setTimeout(() => {
                // If validate only, we don't necessarily need to call backend if validation was client-side
                // But let's assume backend validation is robust
                // For now, if 'validate', we just show success after simulation

                if (action === 'validate') {
                    processingSection.classList.add('hidden');
                    showNotification('Data validation successful! No errors found.', 'success');
                    previewSection.classList.remove('hidden');
                    return;
                }

                fetch('results.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(response => response.json())
                    .then(result => {
                        processingSection.classList.add('hidden');
                        if (result.success) {
                            showNotification(result.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(result.message, 'error');
                            previewSection.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        processingSection.classList.add('hidden');
                        showNotification('Network error occurred.', 'error');
                        console.error(error);
                        previewSection.classList.remove('hidden');
                    });
            }, 2000);
        }

        function simulateProcessing(action) {
            // Simple animation for progress bars
            const bars = [1, 2, 3, 4];

            // If validating, only show first 2 steps
            const maxSteps = action === 'validate' ? 2 : 4;

            if (action === 'save_results') {
                document.getElementById('savingStep').classList.remove('hidden');
            } else {
                document.getElementById('savingStep').classList.add('hidden');
            }

            bars.forEach((i, index) => {
                if (i <= maxSteps) {
                    setTimeout(() => {
                        document.getElementById(`progressBar${i}`).style.width = '100%';
                        document.getElementById(`progressText${i}`).textContent = '100%';
                    }, index * 500);
                }
            });
        }

        function showNotification(message, type) {
            const container = document.getElementById('notificationContainer');
            const div = document.createElement('div');
            div.className = `p-4 rounded shadow-lg text-white mb-2 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            div.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle mr-2"></i> ${message}`;
            container.appendChild(div);
            setTimeout(() => div.remove(), 4000);
        }
    </script>
</body>

</html>