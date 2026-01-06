<?php
/**
 * Test PhpSpreadsheet with user's template file
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = '/var/www/html/nsknbkp1/Template_(Nursery_1).xls';

echo "=== PhpSpreadsheet Validation Test ===\n\n";

// 1. File exists check
if (!file_exists($filePath)) {
    echo "❌ ERROR: File does not exist\n";
    exit(1);
}
echo "✓ File exists: $filePath\n";

// 2. MIME type check
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);
echo "✓ MIME type: $mimeType\n";

// 3. Try to parse with PhpSpreadsheet
echo "\n=== Parsing with PhpSpreadsheet ===\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = [];
    
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    
    echo "✓ File loaded successfully!\n";
    echo "✓ Worksheet: " . $worksheet->getTitle() . "\n";
    echo "✓ Total rows: $highestRow\n";
    echo "✓ Highest column: $highestColumn (index: $highestColumnIndex)\n";
    
    for ($row = 1; $row <= min(5, $highestRow); $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
            $rowData[] = $value !== null ? (string)$value : '';
        }
        $rows[] = $rowData;
    }
    
    echo "\n--- First 5 Rows ---\n";
    foreach ($rows as $i => $row) {
        echo "Row " . ($i + 1) . ": ";
        print_r($row);
    }
    
    echo "\n✅ SUCCESS: PhpSpreadsheet can read binary .xls files!\n";
    echo "✅ VALIDATION RESULT: File is now VALID for upload\n";
    
} catch (Exception $e) {
    echo "❌ Parse ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
