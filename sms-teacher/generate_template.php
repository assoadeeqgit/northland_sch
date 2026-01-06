<?php
/**
 * Strict JSS Excel Template Generator
 * Generates an XML Spreadsheet 2003 file (.xls) with locked student columns and editable score columns.
 */

ob_start(); // Buffer output to prevent whitespace injection
ini_set('display_errors', 0); // Suppress errors to keep XML clean

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$teacher_user_id = $_SESSION['user_id'];

// Validate Input
$class_id = $_GET['class_id'] ?? null;
if (!$class_id) die("Class ID required.");

// --- Fetch Data ---

// 1. Get Class Name
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class_name = $stmt->fetchColumn();
if (!$class_name) die("Invalid Class.");

// 2. Fetch Students (DB-Driven, Locked)
// Using 'student_id' as Admission Number based on previous verification
$stmt = $db->prepare("
    SELECT s.id, s.student_id AS admission_number, u.first_name, u.last_name 
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.class_id = ?
    ORDER BY u.last_name, u.first_name
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Subjects (DB-Ordered)
// We fetch all subjects associated with this class to form the columns
$stmt = $db->prepare("
    SELECT s.id, s.subject_name 
    FROM subjects s
    JOIN class_subjects cs ON s.id = cs.subject_id
    WHERE cs.class_id = ?
    ORDER BY s.subject_name ASC
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($subjects)) {
    die("No subjects assigned to this class. Cannot generate template.");
}

// --- Generate XML Spreadsheet ---

// Filename - Format: Template_(ClassName).xls
$sanitized_class_name = preg_replace('/[^a-zA-Z0-9]/', '_', $class_name);
$filename = "Template_(" . $sanitized_class_name . ").xls";

// Clean Buffer
ob_end_clean();

// Headers
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

echo "<?xml version=\"1.0\"?>\n";
echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>Northland Schools</Author>
  <Created><?php echo date('c'); ?></Created>
 </DocumentProperties>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="sHeader">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#1e3a8a" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="sLocked">
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
   </Borders>
   <Font ss:Color="#555555"/>
   <Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/>
   <Protection ss:Protected="1"/>
  </Style>
  <Style ss:ID="sEditable">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
   </Borders>
   <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
   <Protection ss:Protected="0"/> 
   <NumberFormat ss:Format="Fixed"/>
  </Style>
  <Style ss:ID="sEditableExam">
      <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
      <Borders>
        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/> <!-- Thicker right border to separate subjects -->
      </Borders>
      <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
      <Protection ss:Protected="0"/>
      <NumberFormat ss:Format="Fixed"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="<?= htmlspecialchars($class_name) ?>">
  <Table ss:ExpandedColumnCount="<?= 2 + (count($subjects) * 2) ?>" ss:ExpandedRowCount="<?= count($students) + 2 ?>" x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="15">
   <Column ss:AutoFitWidth="0" ss:Width="100"/> <!-- ID -->
   <Column ss:AutoFitWidth="0" ss:Width="200"/> <!-- Name -->
   <?php foreach($subjects as $sub): ?>
   <Column ss:AutoFitWidth="0" ss:Width="50"/> <!-- CA -->
   <Column ss:AutoFitWidth="0" ss:Width="50"/> <!-- Exam -->
   <?php endforeach; ?>
   
   <!-- Header Row 1: Merged Subject Headers -->
   <Row ss:Height="25">
    <Cell ss:MergeDown="1" ss:StyleID="sHeader"><Data ss:Type="String">STUDENT ID</Data></Cell>
    <Cell ss:MergeDown="1" ss:StyleID="sHeader"><Data ss:Type="String">STUDENT NAME</Data></Cell>
    <?php foreach($subjects as $sub): ?>
    <Cell ss:MergeAcross="1" ss:StyleID="sHeader"><Data ss:Type="String"><?= htmlspecialchars($sub['subject_name']) ?></Data></Cell>
    <?php endforeach; ?>
   </Row>
   
   <!-- Header Row 2: CA/Exam Headers -->
   <Row ss:Height="20">
    <!-- Skip Student ID (1) and Name (2) by starting at Index 3 -->
    <?php foreach($subjects as $i => $sub): ?>
    <Cell ss:StyleID="sHeader" <?= ($i === 0) ? 'ss:Index="3"' : '' ?>><Data ss:Type="String">CA</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">EXAM</Data></Cell>
    <?php endforeach; ?>
   </Row>

   <!-- Data Rows -->
   <?php foreach($students as $stu): ?>
   <Row>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String"><?= $stu['admission_number'] ?></Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String"><?= htmlspecialchars($stu['last_name'] . ' ' . $stu['first_name']) ?></Data></Cell>
    <?php foreach($subjects as $sub): ?>
    <Cell ss:StyleID="sEditable"><Data ss:Type="Number"></Data>
        <DataValidation xmlns="urn:schemas-microsoft-com:office:excel">
            <Type>Decimal</Type>
            <Operator>Between</Operator>
            <Value1>0</Value1>
            <Value2>30</Value2>
            <ErrorStyle>Stop</ErrorStyle>
            <ErrorTitle>Invalid Score</ErrorTitle>
            <ErrorMessage>CA score must be between 0 and 30.</ErrorMessage>
        </DataValidation>
    </Cell>
    <Cell ss:StyleID="sEditableExam"><Data ss:Type="Number"></Data>
        <DataValidation xmlns="urn:schemas-microsoft-com:office:excel">
            <Type>Decimal</Type>
            <Operator>Between</Operator>
            <Value1>0</Value1>
            <Value2>70</Value2>
            <ErrorStyle>Stop</ErrorStyle>
            <ErrorTitle>Invalid Score</ErrorTitle>
            <ErrorMessage>Exam score must be between 0 and 70.</ErrorMessage>
        </DataValidation>
    </Cell>
    <?php endforeach; ?>
   </Row>
   <?php endforeach; ?>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <PageSetup>
    <Layout x:Orientation="Landscape"/>
   </PageSetup>
   <ProtectObjects>True</ProtectObjects>
   <ProtectScenarios>True</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
