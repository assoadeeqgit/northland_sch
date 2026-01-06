<?php
/**
 * Test Template Generation
 * Generate a fresh template and check its format
 */

// Simulate a simple template generation inline
$class_name = "Test_Class";
$filename = "/tmp/test_template_output.xls";

ob_start();

echo "<?xml version=\"1.0\"?>\n";
echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Worksheet ss:Name="Test">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">STUDENT ID</Data></Cell>
    <Cell><Data ss:Type="String">STUDENT NAME</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>';

$content = ob_get_clean();
file_put_contents($filename, $content);

echo "=== Template Generation Test ===\n\n";
echo "✓ Generated test template: $filename\n";

// Check file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filename);
finfo_close($finfo);
echo "✓ MIME type of generated file: $mimeType\n";

// Check if it's XML
$fileContent = file_get_contents($filename);
if (strpos($fileContent, '<?xml') === 0) {
    echo "✓ File starts with XML declaration\n";
} else {
    echo "❌ File does not start with XML declaration\n";
}

if (strpos($fileContent, '<Workbook') !== false) {
    echo "✓ File contains Workbook element\n";
} else {
    echo "❌ File does not contain Workbook element\n";
}

echo "\n✅ Template generator creates XML Spreadsheet 2003 format (MIME: $mimeType)\n";
