<?php
/**
 * Attendance Report Download Handler
 * Generates attendance reports in Excel format for weekly, monthly, and student reports
 */

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    die('Access denied');
}

require_once 'config/database.php';

$class_id = $_GET['class_id'] ?? null;
$type = $_GET['type'] ?? null;

if (!$class_id || !$type) {
    die('Missing required parameters');
}

$database = new Database();
$db = $database->getConnection();

// Get class name
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class_name = $stmt->fetchColumn() ?: 'Unknown Class';

$filename = '';
$reportData = [];

switch ($type) {
    case 'weekly':
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d', strtotime('friday this week'));
        
        $filename = "Weekly_Attendance_Report_{$class_name}_" . date('Y-m-d') . ".xls";
        
        // Fetch attendance data for the week
        $stmt = $db->prepare("
            SELECT 
                s.student_id AS admission_no,
                u.first_name, u.last_name,
                a.attendance_date, a.status
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN attendance a ON s.id = a.student_id AND a.class_id = ?
                AND a.attendance_date BETWEEN ? AND ?
            WHERE s.class_id = ?
            ORDER BY u.last_name, u.first_name, a.attendance_date
        ");
        $stmt->execute([$class_id, $start_date, $end_date, $class_id]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        generateWeeklyReport($reportData, $class_name, $start_date, $end_date);
        break;
        
    case 'monthly':
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $filename = "Monthly_Attendance_Report_{$class_name}_" . date('F_Y', strtotime($start_date)) . ".xls";
        
        // Fetch attendance summary for the month
        $stmt = $db->prepare("
            SELECT 
                s.student_id AS admission_no,
                u.first_name, u.last_name,
                SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) as excused_days,
                COUNT(DISTINCT a.attendance_date) as total_days
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN attendance a ON s.id = a.student_id AND a.class_id = ?
                AND a.attendance_date BETWEEN ? AND ?
            WHERE s.class_id = ?
            GROUP BY s.id, s.student_id, u.first_name, u.last_name
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$class_id, $start_date, $end_date, $class_id]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        generateMonthlyReport($reportData, $class_name, $start_date, $end_date);
        break;
        
    case 'student':
        $all = $_GET['all'] ?? false;
        
        if ($all) {
            $filename = "Student_Attendance_Reports_{$class_name}_" . date('Y-m-d') . ".xls";
            
            // Get all students with their attendance records
            $stmt = $db->prepare("
                SELECT 
                    s.id, s.student_id AS admission_no,
                    u.first_name, u.last_name,
                    a.attendance_date, a.status
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN attendance a ON s.id = a.student_id AND a.class_id = ?
                WHERE s.class_id = ?
                ORDER BY u.last_name, u.first_name, a.attendance_date DESC
                LIMIT 500
            ");
            $stmt->execute([$class_id, $class_id]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            generateStudentReport($reportData, $class_name);
        }
        break;
        
    default:
        die('Invalid report type');
}

function generateWeeklyReport($data, $class_name, $start_date, $end_date) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"Weekly_Attendance_" . date('Ymd') . ".xls\"");
    
    echo "<?xml version=\"1.0\"?>\n";
    echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
    ?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
     xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
     <Styles>
      <Style ss:ID="Header">
       <Font ss:Bold="1" ss:Color="#FFFFFF"/>
       <Interior ss:Color="#1e3a8a" ss:Pattern="Solid"/>
       <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
      </Style>
     </Styles>
     <Worksheet ss:Name="Weekly Report">
      <Table>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Class</Data></Cell>
        <Cell><Data ss:Type="String"><?= $class_name ?></Data></Cell>
       </Row>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Period</Data></Cell>
        <Cell><Data ss:Type="String"><?= date('M d', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></Data></Cell>
       </Row>
       <Row></Row>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Admission No</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Student Name</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Status</Data></Cell>
       </Row>
       <?php foreach ($data as $row): ?>
       <Row>
        <Cell><Data ss:Type="String"><?= $row['admission_no'] ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['first_name'] . ' ' . $row['last_name'] ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['attendance_date'] ?? 'N/A' ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['status'] ?? 'Not Recorded' ?></Data></Cell>
       </Row>
       <?php endforeach; ?>
      </Table>
     </Worksheet>
    </Workbook>
    <?php
    exit;
}

function generateMonthlyReport($data, $class_name, $start_date, $end_date) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"Monthly_Attendance_" . date('Ym') . ".xls\"");
    
    echo "<?xml version=\"1.0\"?>\n";
    echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
    ?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
     xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
     <Styles>
      <Style ss:ID="Header">
       <Font ss:Bold="1" ss:Color="#FFFFFF"/>
       <Interior ss:Color="#1e3a8a" ss:Pattern="Solid"/>
       <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
      </Style>
     </Styles>
     <Worksheet ss:Name="Monthly Summary">
      <Table>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Class</Data></Cell>
        <Cell><Data ss:Type="String"><?= $class_name ?></Data></Cell>
       </Row>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Month</Data></Cell>
        <Cell><Data ss:Type="String"><?= date('F Y', strtotime($start_date)) ?></Data></Cell>
       </Row>
       <Row></Row>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Admission No</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Student Name</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Present</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Absent</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Late</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Excused</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Total Days</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Attendance %</Data></Cell>
       </Row>
       <?php foreach ($data as $row): 
           $percentage = $row['total_days'] > 0 ? round(($row['present_days'] / $row['total_days']) * 100, 1) : 0;
       ?>
       <Row>
        <Cell><Data ss:Type="String"><?= $row['admission_no'] ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['first_name'] . ' ' . $row['last_name'] ?></Data></Cell>
        <Cell><Data ss:Type="Number"><?= $row['present_days'] ?></Data></Cell>
        <Cell><Data ss:Type="Number"><?= $row['absent_days'] ?></Data></Cell>
        <Cell><Data ss:Type="Number"><?= $row['late_days'] ?></Data></Cell>
        <Cell><Data ss:Type="Number"><?= $row['excused_days'] ?></Data></Cell>
        <Cell><Data ss:Type="Number"><?= $row['total_days'] ?></Data></Cell>
        <Cell><Data ss:Type="Number"><?= $percentage ?></Data></Cell>
       </Row>
       <?php endforeach; ?>
      </Table>
     </Worksheet>
    </Workbook>
    <?php
    exit;
}

function generateStudentReport($data, $class_name) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"Student_Attendance_" . date('Ymd') . ".xls\"");
    
    echo "<?xml version=\"1.0\"?>\n";
    echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
    ?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
     xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
     <Styles>
      <Style ss:ID="Header">
       <Font ss:Bold="1" ss:Color="#FFFFFF"/>
       <Interior ss:Color="#1e3a8a" ss:Pattern="Solid"/>
       <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
      </Style>
     </Styles>
     <Worksheet ss:Name="Student Attendance">
      <Table>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Class</Data></Cell>
        <Cell><Data ss:Type="String"><?= $class_name ?></Data></Cell>
       </Row>
       <Row></Row>
       <Row>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Admission No</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Student Name</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell>
        <Cell ss:StyleID="Header"><Data ss:Type="String">Status</Data></Cell>
       </Row>
       <?php foreach ($data as $row): ?>
       <Row>
        <Cell><Data ss:Type="String"><?= $row['admission_no'] ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['first_name'] . ' ' . $row['last_name'] ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['attendance_date'] ?? 'N/A' ?></Data></Cell>
        <Cell><Data ss:Type="String"><?= $row['status'] ?? 'Not Recorded' ?></Data></Cell>
       </Row>
       <?php endforeach; ?>
      </Table>
     </Worksheet>
    </Workbook>
    <?php
    exit;
}
