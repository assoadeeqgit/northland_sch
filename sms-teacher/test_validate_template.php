<?php
/**
 * Test script to validate Template file for upload
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/var/www/html/nsknbkp1/sms-teacher/SimpleXLSXParser.php';

$filePath = '/var/www/html/nsknbkp1/Template_(Nursery_1).xls';

echo "=== Template File Validation ===\n\n";

// 1. File exists check
if (!file_exists($filePath)) {
    echo "❌ ERROR: File does not exist\n";
    exit(1);
}
echo "✓ File exists: $filePath\n";

// 2. File size
$fileSize = filesize($filePath);
echo "✓ File size: " . number_format($fileSize) . " bytes (" . round($fileSize/1024, 2) . " KB)\n";

// 3. MIME type check
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);
echo "✓ MIME type: $mimeType\n";

// 4. File extension check
$fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
echo "✓ File extension: .$fileExtension\n";

// 5. Allowed MIME types (updated list with text/xml)
$allowedMimes = [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/octet-stream',
    'text/xml'
];

if (in_array($mimeType, $allowedMimes)) {
    echo "✓ MIME type is ALLOWED\n";
} else {
    echo "❌ MIME type is NOT ALLOWED\n";
    echo "   Allowed types: " . implode(', ', $allowedMimes) . "\n";
}

// 6. Try to parse the file
echo "\n=== Parsing Test ===\n";
$parser = new SimpleXLSXParser();

try {
    $rows = $parser->parse($filePath);
    echo "✓ File parsed successfully!\n";
    echo "✓ Total rows found: " . count($rows) . "\n";
    
    if (count($rows) >= 3) {
        echo "\n--- Header Row 1 (Subject Names) ---\n";
        print_r($rows[0]);
        
        echo "\n--- Header Row 2 (CA/EXAM) ---\n";
        print_r($rows[1]);
        
        echo "\n--- First Data Row (Sample Student) ---\n";
        print_r($rows[2]);
    }
    
    echo "\n✅ VALIDATION RESULT: File is VALID for upload\n";
    
} catch (Exception $e) {
    echo "❌ Parse ERROR: " . $e->getMessage() . "\n";
    echo "\n❌ VALIDATION RESULT: File is INVALID for upload\n";
    exit(1);
}
