<?php
/**
 * Official Result Sheet Generator
 * Renders a strict, printable HTML page that serves as the PDF export.
 */

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) die("Access denied");

$database = new Database();
$db = $database->getConnection();

// --- Input & Validation ---
$class_id = $_GET['class_id'] ?? null;
$session_id = $_GET['session_id'] ?? null;
$term_id = $_GET['term_id'] ?? null;
$type = $_GET['type'] ?? 'single'; // 'single' or 'bulk'
$target_student_id = $_GET['student_id'] ?? null;

if (!$class_id || !$session_id || !$term_id) die("Missing required parameters.");
if ($type === 'single' && !$target_student_id) die("Student ID required for single report.");

// --- Fetch Data ---

// 1. School Details (Static)
$school_name = "Northland Schools Kano";
$school_address = "Obajana Dantsinke, along Dantsinke Bus stop,<br>Tarauni Local Government Area,<br>Kano State, Nigeria";
$school_phones = "+234 903 175 6416, +234 916 506 4271, +234 706 607 2110";
$school_motto = "DREAM BIG, STUDY HARD & MAKE IT HAPPEN";

// 2. Class & Term Info
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class_name = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT session_name FROM academic_sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session_name = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT term_name FROM terms WHERE id = ?");
$stmt->execute([$term_id]);
$term_name = $stmt->fetchColumn();

// 3. Students
$students = [];
$query = "
    SELECT s.id, s.student_id AS admission_number, u.first_name, u.last_name 
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.class_id = ?
";
$params = [$class_id];

if ($type === 'single') {
    $query .= " AND s.id = ?";
    $params[] = $target_student_id;
} else {
    $query .= " ORDER BY u.last_name, u.first_name";
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($students)) die("No students found.");

// 4. Fetch Results & Calculate Positions (For ALL students in class to determine rank)
// We need positions for the target student(s).
// It's efficient to fetch all ranks for the class first.
$stmtAgg = $db->prepare("
    SELECT student_id, SUM(ca_score + exam_score) as aggregate 
    FROM student_results
    WHERE class_id = ? AND session_id = ? AND term_id = ?
    GROUP BY student_id
    ORDER BY aggregate DESC
");
$stmtAgg->execute([$class_id, $session_id, $term_id]);
$rankings = $stmtAgg->fetchAll(PDO::FETCH_COLUMN, 0); // List of student_ids in order
// Map ID -> Rank
$ranks = [];
foreach ($rankings as $idx => $sid) {
    $ranks[$sid] = $idx + 1;
}
$class_size = count($rankings) ?: count($students); // Fallback to list size if no results

// 5. Fetch Subjects (for mapping)
$stmt = $db->prepare("
    SELECT s.id, s.subject_name 
    FROM subjects s
    JOIN class_subjects cs ON s.id = cs.subject_id
    WHERE cs.class_id = ?
    ORDER BY s.subject_name ASC
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
$subjectMap = [];
foreach($subjects as $s) $subjectMap[$s['id']] = $s['subject_name'];

// 6. Teacher
$stmt = $db->prepare("
    SELECT u.first_name, u.last_name 
    FROM users u
    JOIN teachers t ON u.id = t.user_id 
    JOIN classes c ON c.class_teacher_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
$teacher_name = $teacher ? ($teacher['first_name'] . ' ' . $teacher['last_name']) : "Class Teacher";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Sheet - <?= $class_name ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        body { font-family: 'Roboto', sans-serif; background: #555; }
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 12mm;
            margin: 10mm auto;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        
        /* Force background colors to print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            html, body { 
                background: white; 
                margin: 0; 
                padding: 0;
            }
            .page { 
                margin: 0; 
                box-shadow: none; 
                width: 210mm;
                height: 297mm;
                padding: 8mm 12mm;
                page-break-after: always; 
            }
            .no-print { display: none !important; }
        }
        .header-title { color: #1e3a8a; } /* specific blue */
        .table-borders td, .table-borders th { border: 1px solid black; padding: 2px 4px; }
        
        /* Watermark */
        .page::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 350px;
            height: 350px;
            background-image: url('school_logo.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
            filter: grayscale(100%);
        }
        
        .page > * {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="fixed top-0 left-0 w-full bg-gray-800 text-white p-3 flex justify-between items-center no-print z-50 shadow-lg">
    <div class="font-bold">Report Sheet Preview</div>
    <div>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1 rounded font-bold mr-2">Print / Save as PDF</button>
        <button onclick="window.close()" class="bg-red-600 hover:bg-red-500 text-white px-4 py-1 rounded font-bold">Close</button>
    </div>
</div>
<div class="h-12 no-print"></div>

<?php foreach ($students as $student): 
    $sId = $student['id'];
    
    // Fetch Student Results
    $stmtRes = $db->prepare("
        SELECT subject_id, ca_score, exam_score, total_score, grade, remark 
        FROM student_results
        WHERE student_id = ? AND class_id = ? AND session_id = ? AND term_id = ?
    ");
    $stmtRes->execute([$sId, $class_id, $session_id, $term_id]);
    $results = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary Calcs
    $total_subjects = count($results);
    $total_obtained = 0;
    foreach($results as $r) $total_obtained += $r['total_score'];
    $average = $total_subjects > 0 ? ($total_obtained / $total_subjects) : 0;
    $position = $ranks[$sId] ?? '-';
    
    // Calculate Final Remark logic (simplistic)
    $overall_remark = "FAIR"; // Placeholder
    if ($average >= 70) $overall_remark = "EXCELLENT";
    elseif ($average >= 60) $overall_remark = "VERY GOOD";
    elseif ($average >= 50) $overall_remark = "GOOD";
    
    // Fetch Attendance (Mock/Actual calculation from earlier logic if needed, or simple query)
    // We will do a simple count from attendance table
    $stmtAtt = $db->prepare("
        SELECT COUNT(*) as present 
        FROM attendance 
        WHERE student_id = ? AND class_id = ? AND status = 'Present'
    ");
    $stmtAtt->execute([$sId, $class_id]);
    $days_present = $stmtAtt->fetchColumn();
    // Total days opened? Approx 60 or verify from DB
    $days_opened = 60; 
    $days_absent = $days_opened - $days_present;
?>

<div class="page">
    
    <!-- Header -->
    <div class="text-center border-b-2 border-blue-900 pb-1 mb-2">
        <div class="flex items-center justify-center gap-3 mb-1">
            <img src="school_logo.png" alt="Logo" class="w-14 h-14 object-contain">
            <div>
                <h1 class="text-2xl font-black header-title uppercase tracking-wide"><?= strtoupper($school_name) ?></h1>
                <p class="text-[10px] font-bold text-gray-700"><?= $school_motto ?></p>
            </div>
        </div>
        <div class="text-xs text-gray-600 font-medium">
            <p><?= $school_address ?></p>
            <p class="mt-1">Tel: <?= $school_phones ?></p>
        </div>
        <div class="mt-2 inline-block bg-blue-900 text-white px-6 py-1 font-bold text-base rounded-full">
            <?= strtoupper($term_name) ?> EXAMINATION REPORT SHEET
        </div>
    </div>

    <!-- Student Info -->
    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-3 border border-gray-300 p-2 rounded-lg bg-blue-50/30">
        <div class="flex"><span class="font-bold w-32">Name:</span> <span class="uppercase border-b border-gray-400 flex-grow"><?= $student['last_name'] . ' ' . $student['first_name'] ?></span></div>
        <div class="flex"><span class="font-bold w-32">Admission No:</span> <span class="uppercase border-b border-gray-400 flex-grow"><?= $student['admission_number'] ?></span></div>
        <div class="flex"><span class="font-bold w-32">Class:</span> <span class="uppercase border-b border-gray-400 flex-grow"><?= $class_name ?></span></div>
        <div class="flex"><span class="font-bold w-32">Session:</span> <span class="uppercase border-b border-gray-400 flex-grow"><?= $session_name ?></span></div>
        <div class="flex"><span class="font-bold w-32">Term:</span> <span class="uppercase border-b border-gray-400 flex-grow"><?= $term_name ?></span></div>
        <div class="flex"><span class="font-bold w-32">Position:</span> <span class="uppercase border-b border-gray-400 flex-grow font-bold text-blue-800"><?= ordinal($position) ?></span></div>
        <div class="flex"><span class="font-bold w-32">Class Size:</span> <span class="uppercase border-b border-gray-400 flex-grow"><?= $class_size ?></span></div>
    </div>

    <!-- Results Table -->
    <table class="w-full text-[11px] border-collapse table-borders mb-3">
        <thead>
            <tr class="bg-blue-100 text-blue-900">
                <th class="w-10">S/N</th>
                <th class="text-left">SUBJECT</th>
                <th class="w-16">CA (30)</th>
                <th class="w-16">EXAM (70)</th>
                <th class="w-16">TOTAL (100)</th>
                <th class="w-16">GRADE</th>
                <th class="text-left">REMARK</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            // Iterate all subjects from map to ensure comprehensive list or just results?
            // Usually show all subjects even if 0. Join subjects with results.
            $merged = [];
            foreach($subjectMap as $subId => $subName) {
                $merged[$subId] = ['name' => $subName, 'res' => ['ca_score'=>'-', 'exam_score'=>'-', 'total_score'=>'-', 'grade'=>'-', 'remark'=>'-']];
            }
            foreach($results as $r) {
                if (isset($merged[$r['subject_id']])) {
                    $merged[$r['subject_id']]['res'] = $r;
                }
            }
            
            foreach ($merged as $row): 
                $res = $row['res'];
            ?>
            <tr>
                <td class="text-center"><?= $counter++ ?></td>
                <td class="font-bold"><?= $row['name'] ?></td>
                <td class="text-center"><?= $res['ca_score'] ?></td>
                <td class="text-center"><?= $res['exam_score'] ?></td>
                <td class="text-center font-bold"><?= $res['total_score'] ?></td>
                <td class="text-center font-bold <?= $res['grade'] === 'F' ? 'text-red-600' : '' ?>"><?= $res['grade'] ?></td>
                <td><?= $res['remark'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary & Attendance -->
    <div class="grid grid-cols-2 gap-3 mb-3">
        <!-- Academic Summary -->
        <div class="border border-black p-2">
            <h3 class="font-bold text-xs text-center border-b border-black pb-1 mb-1 bg-gray-100">ACADEMIC SUMMARY</h3>
            <div class="text-xs space-y-1">
                <div class="flex justify-between border-b border-dotted pb-1"><span>Total Subjects:</span> <b><?= $total_subjects ?></b></div>
                <div class="flex justify-between border-b border-dotted pb-1"><span>Total Score:</span> <b><?= $total_obtained ?></b></div>
                <div class="flex justify-between border-b border-dotted pb-1"><span>Average:</span> <b><?= number_format($average, 1) ?>%</b></div>
                <div class="flex justify-between"><span>Overall Remark:</span> <b><?= $overall_remark ?></b></div>
            </div>
        </div>
        
        <!-- Attendance -->
        <div class="border border-black p-2">
            <h3 class="font-bold text-xs text-center border-b border-black pb-1 mb-1 bg-gray-100">ATTENDANCE</h3>
            <div class="text-xs space-y-1">
                <div class="flex justify-between border-b border-dotted pb-1"><span>School Opened:</span> <b><?= $days_opened ?></b></div>
                <div class="flex justify-between border-b border-dotted pb-1"><span>Present:</span> <b><?= $days_present ?></b></div>
                <div class="flex justify-between"><span>Absent:</span> <b><?= $days_absent ?></b></div>
            </div>
        </div>
    </div>

    <!-- Ratings (Optional - Hardcoded placeholder for layout) -->
    <div class="mb-2">
        <h3 class="font-bold text-xs border-b border-black mb-1">BEHAVIOUR & SKILLS</h3>
        <div class="grid grid-cols-4 gap-2 text-[10px]">
            <div>Punctuality: <span class="font-bold">5</span></div>
            <div>Neatness: <span class="font-bold">5</span></div>
            <div>Obedience: <span class="font-bold">5</span></div>
            <div>Politeness: <span class="font-bold">5</span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-t-2 border-black pt-2 mt-auto">
        <div class="grid grid-cols-2 gap-6 text-xs">
            <div>
                <p class="font-bold mb-1 text-[11px]">Class Teacher:</p>
                <div class="border-b border-black h-5 mb-1 font-serif italic text-[10px]"><?= $teacher_name ?></div>
                <p class="text-[10px]">Comment: <span class="italic">A satisfactory performance. keep it up!</span></p>
            </div>
            <div>
                <p class="font-bold mb-1 text-[11px]">Principal:</p>
                <div class="border-b border-black h-5 mb-1"></div>
                <p class="text-[10px]">Date: <?= date('d/m/Y') ?></p>
            </div>
        </div>

</div>

<?php endforeach; ?>

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
</body>
</html>
