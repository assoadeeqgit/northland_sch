<?php
require_once '../auth-check.php';
checkAuth('accountant');
require_once '../config/DatabaseManager.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$dbManager = DatabaseManager::getInstance();
$conn = $dbManager->getConnection();

// Get filter parameters
$class_id = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? $_GET['class_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

// Get active Term ID
$termStmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
$currentTermId = $termStmt->fetchColumn();

// Build Query
$sql = "SELECT 
            s.id,
            s.student_id,
            s.admission_number,
            u.first_name,
            u.last_name,
            c.class_name,
            c.id as class_id
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE u.is_active = 1 AND s.status = 'active'";

$params = [];

if ($class_id) {
    $sql .= " AND s.class_id = ?";
    $params[] = $class_id;
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_id LIKE ? OR s.admission_number LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
}

$sql .= " GROUP BY s.id, s.student_id, s.admission_number, u.first_name, u.last_name, c.class_name, c.id ORDER BY u.first_name, u.last_name";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filename
$filename = "student_list_" . date('Y-m-d') . ".xls";

// Headers for XML Spreadsheet
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
  <Style ss:ID="sDataCenter">
    <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
    <Borders>
        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    </Borders>
  </Style>
  <Style ss:ID="sDataLeft">
    <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
    <Borders>
        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    </Borders>
  </Style>
  <Style ss:ID="sDataNumber">
    <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
    <Borders>
        <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
        <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/>
    </Borders>
    <NumberFormat ss:Format="#,##0.00"/>
  </Style>
  <Style ss:ID="sStatusPaid">
     <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
     <Font ss:Color="#006400" ss:Bold="1"/>
     <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/></Borders>
  </Style>
  <Style ss:ID="sStatusUnpaid">
     <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
     <Font ss:Color="#8B0000" ss:Bold="1"/>
     <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/></Borders>
  </Style>
  <Style ss:ID="sStatusPartial">
     <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
     <Font ss:Color="#FF8C00" ss:Bold="1"/>
     <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D4D4D4"/></Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Student List">
  <Table x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="15">
   <Column ss:AutoFitWidth="0" ss:Width="80"/> <!-- Student ID -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/> <!-- Adm No -->
   <Column ss:AutoFitWidth="0" ss:Width="120"/> <!-- First Name -->
   <Column ss:AutoFitWidth="0" ss:Width="120"/> <!-- Last Name -->
   <Column ss:AutoFitWidth="0" ss:Width="100"/> <!-- Class -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/> <!-- Fee -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/> <!-- Paid -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/> <!-- Balance -->
   <Column ss:AutoFitWidth="0" ss:Width="80"/> <!-- Status -->

   <Row ss:Height="20">
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Student ID</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Admission No</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">First Name</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Last Name</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Class</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Total Fee</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Total Paid</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Balance</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Status</Data></Cell>
   </Row>

<?php
foreach ($students as $student) {
    // Process Fees (Calculations)
    $paidStmt = $conn->prepare("SELECT COALESCE(SUM(amount_paid), 0) as total_paid FROM payments WHERE student_id = ? AND term_id = ?");
    $paidStmt->execute([$student['id'], $currentTermId]);
    $student['total_paid'] = $paidStmt->fetchColumn();

    $feeStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_fee FROM fee_structure WHERE class_id = ? AND term_id = ? AND is_active = 1 AND is_optional = 0");
    $feeStmt->execute([$student['class_id'], $currentTermId]);
    $student['total_fee'] = $feeStmt->fetchColumn() ?: 0;
    
    $student['balance'] = max(0, $student['total_fee'] - $student['total_paid']);
    
    $student_status = 'unpaid';
    $status_style = 'sStatusUnpaid';
    
    if ($student['total_fee'] > 0) {
        if ($student['total_paid'] >= $student['total_fee']) {
            $student_status = 'Fully Paid';
            $status_style = 'sStatusPaid';
        } elseif ($student['total_paid'] > 0) {
            $student_status = 'Partial';
            $status_style = 'sStatusPartial';
        }
    } else {
        $student_status = 'No Fee';
        $status_style = 'sDataCenter';
    }
    
    // Check Status Filter
    $raw_status = strtolower($student_status);
    if ($raw_status == 'fully paid') $raw_status = 'paid';
    
    if ($status && $raw_status !== $status) {
        continue;
    }
?>
   <Row>
    <Cell ss:StyleID="sDataCenter"><Data ss:Type="String"><?= htmlspecialchars($student['student_id']) ?></Data></Cell>
    <Cell ss:StyleID="sDataCenter"><Data ss:Type="String"><?= htmlspecialchars($student['admission_number']) ?></Data></Cell>
    <Cell ss:StyleID="sDataLeft"><Data ss:Type="String"><?= htmlspecialchars($student['first_name']) ?></Data></Cell>
    <Cell ss:StyleID="sDataLeft"><Data ss:Type="String"><?= htmlspecialchars($student['last_name']) ?></Data></Cell>
    <Cell ss:StyleID="sDataLeft"><Data ss:Type="String"><?= htmlspecialchars($student['class_name'] ?: 'Not Assigned') ?></Data></Cell>
    <Cell ss:StyleID="sDataNumber"><Data ss:Type="Number"><?= $student['total_fee'] ?></Data></Cell>
    <Cell ss:StyleID="sDataNumber"><Data ss:Type="Number"><?= $student['total_paid'] ?></Data></Cell>
    <Cell ss:StyleID="sDataNumber"><Data ss:Type="Number"><?= $student['balance'] ?></Data></Cell>
    <Cell ss:StyleID="<?= $status_style ?>"><Data ss:Type="String"><?= htmlspecialchars($student_status) ?></Data></Cell>
   </Row>
<?php } ?>
  </Table>
 </Worksheet>
</Workbook>
