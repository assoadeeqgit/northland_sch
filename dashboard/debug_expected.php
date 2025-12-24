<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get current term and session
$current_session_query = $conn->query("SELECT * FROM academic_sessions ORDER BY id DESC LIMIT 1");
$current_session = $current_session_query->fetch(PDO::FETCH_ASSOC);
$current_session_id = $current_session['id'] ?? 2;

$current_term_query = $conn->query("SELECT * FROM terms ORDER BY id DESC LIMIT 1");
$current_term = $current_term_query->fetch(PDO::FETCH_ASSOC);
$current_term_id = $current_term['id'] ?? 4;

echo "Current Session ID: " . $current_session_id . "\n";
echo "Current Session: " . ($current_session['session_name'] ?? 'N/A') . "\n\n";
echo "Current Term ID: " . $current_term_id . "\n";
echo "Current Term: " . ($current_term['term_name'] ?? 'N/A') . "\n\n";

// Fetch fee structures
$fee_structures_query = $conn->query("
    SELECT 
        fs.*,
        c.class_name,
        t.term_name,
        acs.session_name,
        (SELECT COUNT(*) FROM students s WHERE s.class_id = fs.class_id) as student_count
    FROM fee_structure fs
    LEFT JOIN classes c ON fs.class_id = c.id
    LEFT JOIN terms t ON fs.term_id = t.id
    LEFT JOIN academic_sessions acs ON fs.academic_session_id = acs.id
    WHERE fs.is_active = 1
    ORDER BY fs.created_at DESC
");
$fee_structures = $fee_structures_query->fetchAll(PDO::FETCH_ASSOC);

echo "=== FEE STRUCTURES ===\n";
foreach ($fee_structures as $structure) {
    echo "ID: " . $structure['id'] . "\n";
    echo "Fee Type: " . $structure['fee_type'] . "\n";
    echo "Class: " . $structure['class_name'] . "\n";
    echo "Term ID: " . $structure['term_id'] . " (" . $structure['term_name'] . ")\n";
    echo "Session ID: " . $structure['academic_session_id'] . " (" . $structure['session_name'] . ")\n";
    echo "Amount: " . $structure['amount'] . "\n";
    echo "Students: " . $structure['student_count'] . "\n";
    echo "Matches current term/session? " . ($structure['term_id'] == $current_term_id && $structure['academic_session_id'] == $current_session_id ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

// Calculate Total Expected
$total_expected = 0;
foreach ($fee_structures as $structure) {
    if ($structure['term_id'] == $current_term_id && $structure['academic_session_id'] == $current_session_id) {
        $total_expected += $structure['amount'] * $structure['student_count'];
        echo "Adding: " . $structure['amount'] . " x " . $structure['student_count'] . " = " . ($structure['amount'] * $structure['student_count']) . "\n";
    }
}

echo "\nTotal Expected: ₦" . number_format($total_expected, 2) . "\n";
?>
