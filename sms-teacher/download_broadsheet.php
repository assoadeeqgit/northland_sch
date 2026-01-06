<?php
/**
 * Broadsheet Export
 * Generates a populated Excel Broadheet with all results.
 */

ob_start();
ini_set('display_errors', 0);

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) die("Access denied");

$database = new Database();
$db = $database->getConnection();

$class_id = $_GET['class_id'] ?? null;
$session_id = $_GET['session_id'] ?? null;
$term_id = $_GET['term_id'] ?? null;

if (!$class_id) die("Class ID required.");

// Fetch Data (Similar to Template, plus Results)

// 1. Class Name
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class_name = $stmt->fetchColumn();

// 2. Students
$stmt = $db->prepare("
    SELECT s.id, s.student_id AS admission_number, u.first_name, u.last_name 
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.class_id = ?
    ORDER BY u.last_name, u.first_name
");
$stmt->execute([$class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Subjects
$stmt = $db->prepare("
    SELECT s.id, s.subject_name 
    FROM subjects s
    JOIN class_subjects cs ON s.id = cs.subject_id
    WHERE cs.class_id = ?
    ORDER BY s.subject_name ASC
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Results
// Fetch all results for this class/session/term
$stmt = $db->prepare("
    SELECT student_id, subject_id, ca_score, exam_score 
    FROM student_results 
    WHERE class_id = ? AND session_id = ? AND term_id = ?
");
$stmt->execute([$class_id, $session_id, $term_id]);
$rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pivot Results: [student_id][subject_id] => ['ca', 'exam']
$mappedResults = [];
foreach ($rawResults as $r) {
    $mappedResults[$r['student_id']][$r['subject_id']] = [
        'ca' => $r['ca_score'],
        'exam' => $r['exam_score']
    ];
}

// 5. Calculate Totals and Positions
$studentTotals = [];
foreach ($students as $stu) {
    $sId = $stu['id'];
    $total = 0;
    
    // Sum all CA + EXAM scores for this student
    if (isset($mappedResults[$sId])) {
        foreach ($mappedResults[$sId] as $subId => $scores) {
            $ca = is_numeric($scores['ca']) ? $scores['ca'] : 0;
            $exam = is_numeric($scores['exam']) ? $scores['exam'] : 0;
            $total += ($ca + $exam);
        }
    }
    
    $studentTotals[$sId] = $total;
}

// Sort by total descending to get positions
arsort($studentTotals);
$positions = [];
$rank = 1;
foreach ($studentTotals as $sId => $total) {
    $positions[$sId] = $rank++;
}

// Generate Excel
$filename = "Broadsheet_Report_" . preg_replace('/[^a-zA-Z0-9]/', '_', $class_name) . ".xls";
ob_end_clean();

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo "<?xml version=\"1.0\"?>\n";
echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="sHeader">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#1e3a8a" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="sData">
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
   </Borders>
  </Style>
  <Style ss:ID="sLocked">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
   </Borders>
   <Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Broadsheet Report">
  <Table ss:ExpandedColumnCount="<?= 2 + (count($subjects) * 2) + 2 ?>" ss:ExpandedRowCount="<?= count($students) + 10 ?>" x:FullColumns="1" x:FullRows="1">
   <Column ss:Width="80"/>
   <Column ss:Width="180"/>
   <?php foreach($subjects as $sub): ?>
   <Column ss:Width="40"/>
   <Column ss:Width="40"/>
   <?php endforeach; ?>
   <Column ss:Width="60"/>
   <Column ss:Width="60"/>
   
   <Row ss:Height="25">
    <Cell ss:MergeDown="1" ss:StyleID="sHeader"><Data ss:Type="String">STUDENT ID</Data></Cell>
    <Cell ss:MergeDown="1" ss:StyleID="sHeader"><Data ss:Type="String">STUDENT NAME</Data></Cell>
    <?php foreach($subjects as $sub): ?>
    <Cell ss:MergeAcross="1" ss:StyleID="sHeader"><Data ss:Type="String"><?= htmlspecialchars($sub['subject_name']) ?></Data></Cell>
    <?php endforeach; ?>
    <Cell ss:MergeDown="1" ss:StyleID="sHeader"><Data ss:Type="String">TOTAL</Data></Cell>
    <Cell ss:MergeDown="1" ss:StyleID="sHeader"><Data ss:Type="String">POSITION</Data></Cell>
   </Row>
   <Row>
    <?php 
    $firstSubject = true;
    foreach($subjects as $sub): 
    ?>
    <Cell <?= $firstSubject ? 'ss:Index="3" ' : '' ?>ss:StyleID="sHeader"><Data ss:Type="String">CA</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">EXAM</Data></Cell>
    <?php 
    $firstSubject = false;
    endforeach; 
    ?>
   </Row>
   
   <?php foreach($students as $stu): 
       $sId = $stu['id'];
   ?>
   <Row>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String"><?= $stu['admission_number'] ?></Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String"><?= htmlspecialchars($stu['last_name'] . ' ' . $stu['first_name']) ?></Data></Cell>
    <?php foreach($subjects as $sub): 
        $subId = $sub['id'];
        $ca = $mappedResults[$sId][$subId]['ca'] ?? '';
        $exam = $mappedResults[$sId][$subId]['exam'] ?? '';
    ?>
    <Cell ss:StyleID="sData"><Data ss:Type="<?= is_numeric($ca) ? 'Number' : 'String' ?>"><?= $ca ?></Data></Cell>
    <Cell ss:StyleID="sData"><Data ss:Type="<?= is_numeric($exam) ? 'Number' : 'String' ?>"><?= $exam ?></Data></Cell>
    <?php endforeach;    ?>
     <Cell ss:StyleID="sData"><Data ss:Type="Number"><?= $studentTotals[$sId] ?? 0 ?></Data></Cell>
     <Cell ss:StyleID="sData"><Data ss:Type="String"><?= ordinal($positions[$sId] ?? 0) ?></Data></Cell>
    </Row>
    <?php endforeach; ?>
   </Table>
  </Worksheet>
</Workbook>
<?php
function ordinal($number) {
    if (!is_numeric($number)) return $number;
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}
?>
