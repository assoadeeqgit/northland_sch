<?php
$data = [
    ['student_id', 'name', 'ca_score', 'exam_score'],
    [1, 'Yusuf Sani', 15, 60],
    [2, 'Fatima Kareem', 18, 55],
    [3, 'SAFIYYA AHMAD', 12, 70],
    [4, 'Hassan Musa', 20, 65],
    [5, 'Ahmad Sani', 16, 50]
];

$xml = '<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Worksheet ss:Name="Sheet1">
  <Table>';

foreach ($data as $row) {
    $xml .= '<Row>';
    foreach ($row as $cell) {
        $type = is_numeric($cell) ? 'Number' : 'String';
        $xml .= '<Cell><Data ss:Type="' . $type . '">' . $cell . '</Data></Cell>';
    }
    $xml .= '</Row>';
}

$xml .= '  </Table>
 </Worksheet>
</Workbook>';

$file = 'templates/primary_school_template.xls';
if (!is_dir('templates')) {
    mkdir('templates', 0777, true);
}
file_put_contents($file, $xml);
echo "Successfully created $file";
?>
